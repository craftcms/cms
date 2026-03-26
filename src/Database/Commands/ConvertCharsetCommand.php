<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Commands\Concerns\ManagesDatabaseTables;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;
use Override;

use function Laravel\Prompts\suggest;

class ConvertCharsetCommand extends Command
{
    use CraftCommand;
    use ManagesDatabaseTables;

    #[Override]
    protected $signature = 'craft:db:convert-charset {charset?} {collation?}';

    #[Override]
    protected $description = "Converts tables' character sets and collations. (MySQL only)";

    #[Override]
    protected $aliases = ['db/convert-charset'];

    public function handle(Connection $connection): int
    {
        $charset = $this->argument('charset');
        $collation = $this->argument('collation');
        $charset = is_string($charset) ? $charset : null;
        $collation = is_string($collation) ? $collation : null;

        if (! $connection->isMysql()) {
            $this->components->error('This command is only available when using MySQL.');

            return self::FAILURE;
        }

        $schema = Schema::connection($connection->getName());
        $tableNames = $this->tableNames($connection);

        if (empty($tableNames)) {
            $this->components->error('Could not find any database tables.');

            return self::FAILURE;
        }

        $defaultCharset = (string) ($connection->getConfig('charset') ?? 'utf8mb4');
        $defaultCollation = (string) ($connection->getConfig('collation') ?? $this->defaultCollation($connection));

        $charset ??= $this->input->isInteractive()
            ? suggest(
                label: 'Which character set should be used?',
                options: $this->charsetSuggestions($connection, $defaultCharset),
                default: $defaultCharset,
                required: true,
            )
            : $defaultCharset;

        $collation ??= $this->input->isInteractive()
            ? suggest(
                label: 'Which collation should be used?',
                options: $this->collationSuggestions($connection, $defaultCollation),
                default: $defaultCollation,
                required: true,
            )
            : $defaultCollation;

        $schema->disableForeignKeyConstraints();

        try {
            foreach ($tableNames as $tableName) {
                $this->components->task("Converting $tableName", function () use ($charset, $collation, $connection, $tableName) {
                    $wrappedTable = $connection->getSchemaGrammar()->wrapTable($tableName);
                    $connection->statement("ALTER TABLE $wrappedTable CONVERT TO CHARACTER SET $charset COLLATE $collation");
                });
            }
        } finally {
            $schema->enableForeignKeyConstraints();
        }

        $this->components->success("Finished converting tables to $charset/$collation.");

        return self::SUCCESS;
    }

    private function defaultCollation(Connection $connection): string
    {
        if (! $connection->isMaria() && version_compare($connection->getServerVersion(), '8.0', '>=')) {
            return 'utf8mb4_0900_ai_ci';
        }

        return 'utf8mb4_unicode_ci';
    }

    /** @return list<string> */
    private function charsetSuggestions(Connection $connection, string $default): array
    {
        return collect($connection->select('SHOW CHARACTER SET'))
            ->map(function (object $row): ?string {
                $values = (array) $row;

                return $values['Charset'] ?? $values['charset'] ?? null;
            })
            ->filter(fn (mixed $charset): bool => is_string($charset) && $charset !== '')
            ->prepend($default)
            ->unique()
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function collationSuggestions(Connection $connection, string $default): array
    {
        return collect($connection->select('SHOW COLLATION'))
            ->map(function (object $row): ?string {
                $values = (array) $row;

                return $values['Collation'] ?? $values['collation'] ?? null;
            })
            ->filter(fn (mixed $collation): bool => is_string($collation) && $collation !== '')
            ->prepend($default)
            ->unique()
            ->values()
            ->all();
    }
}
