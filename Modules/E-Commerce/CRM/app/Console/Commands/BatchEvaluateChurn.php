<?php

namespace Modules\Ecommerce\CRM\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\CRM\Services\ChurnRiskService;

class BatchEvaluateChurn extends Command
{
    protected $signature = 'crm:evaluate-churn
        {--client-id= : Optional client ID to scope the evaluation}
        {--progress : Show a progress bar}';

    protected $description = 'Batch-evaluate churn risk for all customers and update their risk labels';

    public function handle(ChurnRiskService $service): int
    {
        $clientId = $this->option('client-id') ? (int) $this->option('client-id') : null;
        $showProgress = $this->option('progress');

        // Set the client context so the BelongsToClient global scope doesn't filter out all records
        if ($clientId) {
            app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->setClientId($clientId);
        }

        $this->info('Running churn risk batch evaluation...');

        if ($showProgress) {
            $total = Customer::query()
                ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
                ->count();
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $results = $service->batchEvaluateAll($clientId, function () use ($bar) {
                $bar->advance();
            });

            $bar->finish();
            $this->newLine();
        } else {
            $results = $service->batchEvaluateAll($clientId);
        }

        $this->line('');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Customers evaluated', $results['processed']],
                ['Low risk', $results['low']],
                ['Medium risk', $results['medium']],
                ['High risk', $results['high']],
                ['Errors', $results['errors']],
            ]
        );

        $this->info('Churn risk evaluation complete!');

        return Command::SUCCESS;
    }
}
