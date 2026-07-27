<?php

namespace Modules\Ecommerce\CRM\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ecommerce\CRM\Models\Customer;
use Modules\Ecommerce\CRM\Services\RfmSegmentEngine;

class BatchEvaluateRfm extends Command
{
    protected $signature = 'crm:evaluate-rfm
        {--client-id= : Optional client ID to scope the evaluation}
        {--progress : Show a progress bar}';

    protected $description = 'Batch-evaluate RFM scores for all customers and sync auto-segment assignments';

    public function handle(RfmSegmentEngine $engine): int
    {
        $clientId = $this->option('client-id') ? (int) $this->option('client-id') : null;
        $showProgress = $this->option('progress');

        // Set the client context so the BelongsToClient global scope doesn't filter out all records
        if ($clientId) {
            app(\Modules\Ecommerce\Support\EcommerceClientContext::class)->setClientId($clientId);
        }

        $this->info('Running RFM batch evaluation...');

        if ($showProgress) {
            $total = Customer::query()
                ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
                ->count();
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $scoreResults = $engine->batchScoreAll($clientId, function () use ($bar) {
                $bar->advance();
            });

            $bar->finish();
            $this->newLine();
        } else {
            $scoreResults = $engine->batchScoreAll($clientId);
        }

        $this->line('');

        // Then evaluate auto-segments
        $segmentResults = $engine->evaluateAutoSegments($clientId);

        // Display results
        $this->table(
            ['Metric', 'Count'],
            [
                ['Customers scored', $scoreResults['processed']],
                ['Errors', $scoreResults['errors']],
                ['Auto-segments evaluated', $segmentResults['segments_evaluated']],
                ['Customers assigned', $segmentResults['customers_assigned']],
            ]
        );

        if (!empty($scoreResults['segment_counts'])) {
            $this->line('');
            $this->info('Segment distribution:');
            $rows = [];
            arsort($scoreResults['segment_counts']);
            foreach ($scoreResults['segment_counts'] as $slug => $count) {
                $rows[] = [ucfirst(str_replace('_', ' ', $slug)), $count];
            }
            $this->table(['Segment', 'Customers'], $rows);
        }

        $this->info('RFM evaluation complete!');

        return Command::SUCCESS;
    }
}
