<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequisitionController extends Controller
{
    private function getRequisitionConnection()
    {
        foreach (['order_fulfillment', 'manufacturing'] as $connectionName) {
            try {
                $connection = DB::connection($connectionName);
                if ($connection->getSchemaBuilder()->hasTable('requisitions')) {
                    return $connection;
                }
            } catch (\Exception $e) {
                // ignore broken or unavailable external DB connections
            }
        }

        throw new \RuntimeException('No external requisition source is available.');
    }

    private function getRequisitionConnections(): array
    {
        $connections = [];

        foreach (['order_fulfillment', 'manufacturing'] as $connectionName) {
            try {
                $connection = DB::connection($connectionName);
                if ($connection->getSchemaBuilder()->hasTable('requisitions')) {
                    $connections[] = $connection;
                }
            } catch (\Exception $e) {
                // ignore broken or unavailable external DB connections
            }
        }

        if ($connections === []) {
            throw new \RuntimeException('No external requisition source is available.');
        }

        return $connections;
    }

    private function getWritableRequisitionConnection()
    {
        return $this->getRequisitionConnection();
    }

    /**
     * Find which external connection (orderfullfillment or manufacturing)
     * actually holds the requisition with this id. Previously update()/
     * destroy() always used the first connection that had a "requisitions"
     * table (orderfullfillment), so status changes and deletes for
     * requisitions that actually came from "manufacturing" silently
     * touched zero rows there instead of the real record.
     */
    private function findRequisitionConnectionFor($id)
    {
        foreach ($this->getRequisitionConnections() as $connection) {
            if ($connection->table('requisitions')->where('id', $id)->exists()) {
                return $connection;
            }
        }

        // Fall back to the old behavior rather than erroring out.
        return $this->getRequisitionConnection();
    }

    private function ensureRequisitionTable($connection): void
    {
        if ($connection->getSchemaBuilder()->hasTable('requisitions')) {
            return;
        }

        throw new \RuntimeException(sprintf('The requisition table is not available on connection %s.', $connection->getName()));
    }

    private function requisitionHasColumn($connection, string $column): bool
    {
        try {
            return $connection->getSchemaBuilder()->hasColumn('requisitions', $column);
        } catch (\Exception $e) {
            return false;
        }
    }

       private function isDuplicateKeyException(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'duplicate key')
            || str_contains($message, 'Unique violation')
            || str_contains($message, 'SQLSTATE[23505]')
            || str_contains($message, 'UNIQUE constraint failed');
    }

    private function makeUniqueRequisitionInsert(array $insert): array
    {
        $clone = $insert;
        $suffix = now()->format('YmdHis') . '-' . random_int(1000, 9999);

        if (array_key_exists('req_number', $clone) && ! empty($clone['req_number'])) {
            $clone['req_number'] = $clone['req_number'] . '-' . $suffix;
        } elseif (array_key_exists('req_id', $clone) && ! empty($clone['req_id'])) {
            $clone['req_id'] = $clone['req_id'] . '-' . $suffix;
        }

        return $clone;
    }

    private function insertRequisition($connection, array $insert): int
    {
        $attempts = 0;
        $currentConnection = $connection;
        $currentInsert = $insert;

        while ($attempts < 3) {
            try {
                return $currentConnection->table('requisitions')->insertGetId($currentInsert);
            } catch (\Throwable $e) {
                if ($this->isDuplicateKeyException($e)) {
                    $currentInsert = $this->makeUniqueRequisitionInsert($currentInsert);
                    $attempts++;
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Unable to save requisition after retrying.');
    }

    private function getRequisitionSelectFields($connection): array
    {
        // Order Fulfillment and Manufacturing intentionally use different
        // requisition schemas.  Keep the mapping tied to the actual Laravel
        // connection name: a previous spelling of `order_fulfillment` here
        // made the fulfillment rows select Manufacturing-only columns
        // (`req_id`, `part_name`, and `quantity`) and broke the inbox page.
        if ($connection->getName() === 'order_fulfillment') {
            return [
                'id',
                'req_number as requisition_number',
                'item',
                'qty as qty',
                'department',
                'requested_by',
                'priority',
                DB::raw("'Pending' as status"),
                'date_requested as request_date',
                'notes',
                'created_at',
                'updated_at',
            ];
        }

        return [
            'id',
            'req_id as requisition_number',
            'part_name as item',
            'quantity as qty',
            'department',
            'requested_by',
            'priority',
            'status',
            'date_requested as request_date',
            'notes',
            'destination',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Requisitions list page (filters, sortable table, add requisition modal).
     */
    public function index(Request $request)
    {
        $requisitions = collect();
        $clientId = (int) session('employee_client_id');
        $rootTesting = config('nexora.root_admin_module_testing')
            && $request->user()?->role === 'root_admin';

        foreach ($this->getRequisitionConnections() as $connection) {
            $this->ensureRequisitionTable($connection);
            $query = $connection
                ->table('requisitions')
                ->select($this->getRequisitionSelectFields($connection));

            // Both current source schemas are client-scoped.  Retain the
            // legacy-column guard so a not-yet-upgraded Manufacturing schema
            // does not turn this page into a server error.
            if (! $rootTesting && $this->requisitionHasColumn($connection, 'client_id')) {
                $query->where('client_id', $clientId);
            }

            $connectionRequisitions = $query
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($connectionRequisitions as $req) {
                $req->source_connection = $connection->getName();
                $requisitions->push($req);
            }
        }

        $requisitions = $requisitions->sortByDesc('created_at')->values();
        $requisitionRefs = $requisitions->pluck('requisition_number')->filter()->all();

        $purchaseOrders = collect();
        if ($requisitionRefs !== []) {
            $purchaseOrderQuery = DB::connection('procurement')
                ->table('purchase_orders')
                ->whereIn('requisition_reference', $requisitionRefs);

            if (! $rootTesting) {
                $purchaseOrderQuery->where('client_id', $clientId);
            }

            $purchaseOrders = $purchaseOrderQuery
                ->get()
                ->keyBy('requisition_reference');
        }

        $requisitions = $requisitions->map(function ($req) use ($purchaseOrders) {
            $ref = $req->requisition_number;
            $po = $purchaseOrders->get($ref);

            if ($po) {
                $poStatus = strtolower(trim($po->status ?? 'pending'));
                $currentStatus = strtolower(trim($req->status ?? 'pending'));
                if (in_array($currentStatus, ['pending', 'processing', ''], true)) {
                    if (in_array($poStatus, ['pending', 'approved', 'processing'], true)) {
                        $req->status = 'Processing';
                    } elseif ($poStatus === 'completed') {
                        $req->status = 'Completed';
                    }
                }
                $req->po_number = $po->po_number;
                $req->po_status = $po->status;
            }

            return $req;
        });

        $statusCounts = $requisitions->map(function ($req) {
            return strtolower(str_replace(' ', '-', $req->status ?? 'Pending'));
        })->countBy();

        return view('procurement::pages.requisitions', compact('requisitions', 'statusCounts'));
    }

    /**
     * Handle the "+ New Requisition" modal submit (submitAddReq in app-forms.js).
     */
    public function store(Request $request)
    {
        return response()->json(['status' => 'ok', 'message' => 'Requisition creation is disabled.']);
    }

    public function update(Request $request, $requisition)
    {
        $validated = $request->validate([
            'status' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $connection = $this->findRequisitionConnectionFor($requisition);
        $update = ['updated_at' => now()];

        if ($this->requisitionHasColumn($connection, 'status') && ! empty($validated['status'])) {
            $update['status'] = $validated['status'];
        }

        if ($this->requisitionHasColumn($connection, 'notes')) {
            $update['notes'] = $validated['notes'] ?? DB::raw('notes');
        }

        $connection->table('requisitions')->where('id', $requisition)->update($update);

        return response()->json(['status' => 'ok']);
    }

    public function destroy($requisition)
    {
        $connection = $this->findRequisitionConnectionFor($requisition);
        $connection->table('requisitions')->where('id', $requisition)->delete();

        return response()->json(['status' => 'ok']);
    }
}
