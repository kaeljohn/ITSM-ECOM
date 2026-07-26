<?php

namespace Modules\Ecommerce\CRM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\CRM\Models\Customer;

class SyncCrmCustomers extends Command
{
    protected $signature = 'crm:sync-customers {--from-scratch : Truncate and re-sync all customer profiles}';
    protected $description = 'Backfill CRM customer profiles from ecommerce users and orders';

    public function handle(): int
    {
        if ($this->option('from-scratch')) {
            Customer::truncate();
            $this->info('Truncated existing CRM customer profiles.');
        }

        // Sync from ecommerce users
        $users = DB::connection('ecommerce')->table('users')->get();
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $synced = 0;
        foreach ($users as $user) {
            try {
                // Calculate order stats
                $orderStats = DB::connection('ecommerce')->table('orders')
                    ->where('user_id', $user->id)
                    ->where('status', '!=', 'cancelled')
                    ->selectRaw('count(*) as order_count')
                    ->selectRaw('coalesce(sum(total), 0) as total_spent')
                    ->first();

                Customer::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'client_id' => $user->client_id ?? null,
                        'email' => $user->email,
                        'first_name' => $user->first_name ?? explode('@', $user->email)[0],
                        'phone' => $user->phone ?? null,
                        'total_spent' => $orderStats->total_spent ?? 0,
                        'order_count' => $orderStats->order_count ?? 0,
                        'average_order_value' => ($orderStats->order_count ?? 0) > 0
                            ? round(($orderStats->total_spent ?? 0) / $orderStats->order_count, 2)
                            : 0,
                        'last_purchase_at' => DB::connection('ecommerce')->table('orders')
                            ->where('user_id', $user->id)
                            ->where('status', '!=', 'cancelled')
                            ->max('created_at'),
                    ]
                );
                $synced++;
            } catch (\Throwable $e) {
                $this->warn("Failed to sync user {$user->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced {$synced} customer profiles.");

        return Command::SUCCESS;
    }
}
