<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Commands\Concerns\ManagesDatabaseTables;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;
use Override;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

final class DropTablePrefixCommand extends Command
{
    use CraftCommand;
    use ManagesDatabaseTables;

    #[Override]
    protected $signature = 'craft:db:drop-table-prefix
        {prefix? : The current table prefix. If omitted, the prefix will be detected automatically.}
    ';

    #[Override]
    protected $description = 'Drops the database table prefix from all tables.';

    #[Override]
    protected $aliases = ['db/drop-table-prefix'];

    public function handle(Connection $connection): int
    {
        $prefix = $this->argument('prefix') ?? $this->detectPrefix($connection);

        if ($prefix === false) {
            return self::FAILURE;
        }

        $prefix = Str::finish($prefix, '_');

        $this->components->warn("All database tables named `$prefix*` will be renamed to drop the table prefix.\nThis should NOT be run on production!");

        if (! confirm('Are you sure you want to proceed?')) {
            return self::SUCCESS;
        }

        $quotedPrefix = preg_quote($prefix, '/');

        foreach ($this->tableNames($connection) as $oldName) {
            if (! str_starts_with($oldName, $prefix)) {
                continue;
            }

            $newName = preg_replace("/^$quotedPrefix/", '', $oldName);

            $this->components->task(
                "Renaming `$oldName` to `$newName`",
                function () use ($connection, $oldName, $newName) {
                    $this->renameTable($oldName, $newName, $connection);
                },
            );
        }

        $this->components->success('Database tables renamed.');

        if ($connection->getTablePrefix() !== '') {
            $this->components->warn("Don't forget to clear out your `tablePrefix` database setting.");
        }

        return self::SUCCESS;
    }

    private function detectPrefix(Connection $connection): string|false
    {
        $this->components->info('Detecting the current table prefix ...');

        $patterns = array_map(
            fn (string $name) => "/^(\\w+)_$name$/",
            [Table::ELEMENTS, Table::ENTRIES, Table::INFO, Table::SECTIONS],
        );

        $foundPrefixes = [];

        foreach ($this->tableNames($connection) as $name) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $name, $match)) {
                    if (! isset($foundPrefixes[$match[1]])) {
                        $foundPrefixes[$match[1]] = 1;
                    } else {
                        $foundPrefixes[$match[1]]++;
                    }

                    break;
                }
            }
        }

        $possiblePrefixes = [];
        foreach ($foundPrefixes as $prefix => $count) {
            if ($count === count($patterns)) {
                $possiblePrefixes[] = $prefix;
            }
        }

        if (empty($possiblePrefixes)) {
            $this->components->error('No current table prefix appears to be in use.');

            return false;
        }

        if (count($possiblePrefixes) === 1) {
            $prefix = reset($possiblePrefixes);
            $this->components->info("`$prefix` detected.");

            return $prefix;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('Multiple table prefixes were detected. Run the command again with the current prefix specified.');

            return false;
        }

        return select(
            label: 'Multiple table prefixes were detected. Which one should be removed?',
            options: array_combine($possiblePrefixes, $possiblePrefixes),
        );
    }

    private function renameTable(string $table, string $newName, Connection $connection): void
    {
        $schema = Schema::connection($connection->getName());
        $schema->rename($table, $newName);

        if (! $connection->isPgsql()) {
            return;
        }

        [$schemaName, $rawOldName] = $schema->parseSchemaAndTable($table, withDefaultSchema: true);
        [, $rawNewName] = $schema->parseSchemaAndTable($newName, withDefaultSchema: true);
        $oldSequence = "{$rawOldName}_id_seq";
        $newSequence = "{$rawNewName}_id_seq";
        $qualifiedOldSequence = $schemaName ? "$schemaName.$oldSequence" : $oldSequence;
        $grammar = $connection->getQueryGrammar();

        $connection->transaction(function (Connection $connection) use ($grammar, $qualifiedOldSequence, $newSequence): void {
            $connection->statement(sprintf(
                'ALTER SEQUENCE %s RENAME TO %s',
                $grammar->wrapTable($qualifiedOldSequence),
                $grammar->wrap($newSequence),
            ));
        });
    }
}
