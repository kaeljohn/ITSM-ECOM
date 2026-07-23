<?php

namespace Modules\Inventory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureInventoryHrActorColumns extends Command
{
    protected $signature = 'inventory:ensure-hr-actor-columns';

    protected $description = 'Remove legacy Inventory users foreign keys so HR employee IDs can be stored as audit actors';

    public function handle(): int
    {
        $connection = DB::connection('inventory');

        if ($connection->getDriverName() !== 'pgsql') {
            return self::SUCCESS;
        }

        $schema = Schema::connection('inventory');

        foreach ([
            ['table' => 'stock_movements', 'column' => 'performed_by'],
            ['table' => 'stock_receivings', 'column' => 'processed_by'],
        ] as $target) {
            if (! $schema->hasTable($target['table']) || ! $schema->hasColumn($target['table'], $target['column'])) {
                continue;
            }

            $constraints = $connection->select(
                <<<'SQL'
                    SELECT constraint_name
                    FROM information_schema.key_column_usage
                    WHERE table_schema = current_schema()
                      AND table_name = ?
                      AND column_name = ?
                      AND constraint_name IN (
                        SELECT conname FROM pg_constraint WHERE contype = 'f'
                      )
                SQL,
                [$target['table'], $target['column']]
            );

            foreach ($constraints as $constraint) {
                $name = '"'.str_replace('"', '""', $constraint->constraint_name).'"';
                $table = '"'.str_replace('"', '""', $target['table']).'"';
                $connection->statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$name}");
                $this->info("Removed legacy {$constraint->constraint_name} foreign key.");
            }
        }

        return self::SUCCESS;
    }
}
