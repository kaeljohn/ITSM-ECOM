<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$companies = DB::table('companies')
    ->whereRaw('LOWER(company_name) LIKE ?', ['%amd%'])
    ->get(['id', 'company_name']);

$report = ['companies' => $companies, 'connections' => []];

foreach (['inventory' => ['warehouses', 'items', 'stock_levels'], 'procurement' => ['suppliers', 'purchase_orders', 'deliveries']] as $connection => $tables) {
    foreach ($tables as $table) {
        try {
            $hasClientId = Schema::connection($connection)->hasColumn($table, 'client_id');
            $rows = DB::connection($connection)->table($table)->count();
            $perClient = $hasClientId
                ? DB::connection($connection)->table($table)->selectRaw('client_id, count(*) as total')->groupBy('client_id')->orderBy('client_id')->get()
                : [];

            $report['connections'][$connection][$table] = compact('hasClientId', 'rows', 'perClient');
        } catch (Throwable $exception) {
            $report['connections'][$connection][$table] = ['error' => $exception->getMessage()];
        }
    }
}

$report['inventory_detail'] = [
    'warehouses' => DB::connection('inventory')->table('warehouses')->get(['id', 'client_id', 'name']),
    'stock_by_warehouse' => DB::connection('inventory')->table('stock_levels')
        ->selectRaw('warehouse_id, client_id, count(*) as records, sum(stock) as units')
        ->groupBy('warehouse_id', 'client_id')->orderBy('warehouse_id')->get(),
];

$procurementUrl = (string) config('database.connections.procurement.url');
if ($procurementUrl !== '') {
    config(['database.connections.procurement.url' => preg_replace('#/neondb\?#', '/Nex_Procurement2?', $procurementUrl)]);
    DB::purge('procurement');

    try {
        $report['legacy_procurement_database'] = [
            'suppliers' => DB::connection('procurement')->table('suppliers')->count(),
            'purchase_orders' => DB::connection('procurement')->table('purchase_orders')->count(),
            'deliveries' => DB::connection('procurement')->table('deliveries')->count(),
        ];
    } catch (Throwable $exception) {
        $report['legacy_procurement_database'] = ['error' => $exception->getMessage()];
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
