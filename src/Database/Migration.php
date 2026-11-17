<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Migrations\Migration as LaravelMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @since 6.0.0
 */
abstract class Migration extends LaravelMigration
{
    use InteractsWithIO;

    protected bool $silent = false;

    public function __construct()
    {
        $this->input = new ArrayInput([]);
        $this->output = new OutputStyle($this->input, new ConsoleOutput);
        $this->components = new Factory($this->output);
    }

    public function silent(): self
    {
        $this->silent = true;
        $this->output = new OutputStyle($this->input, new NullOutput);
        $this->components = new Factory($this->output);

        return $this;
    }

    /**
     * Renames a table to `{table}_old` (or `{table}_old1`, `{table}_old2`, …) if it exists, so a new one can be
     * created in its place.
     *
     * Install migrations can call this before creating their tables, to keep tables left over from a previous install,
     * such as in a database restored from another environment.
     *
     * The archived table's indexes, and its `id` sequence on PostgreSQL, are renamed along with it, and its foreign
     * keys are dropped on MySQL, since those names must be unique across the database.
     *
     * Names are kept within the database's identifier length limit, including the table prefix: the table name is
     * shortened before its `_old` suffix, and longer index and sequence names end with a hash of the full name.
     *
     * @throws RuntimeException if the table prefix leaves no room for the archived table's name
     */
    public function archiveTableIfExists(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $driver = DB::getDriverName();
        $prefix = DB::getTablePrefix();
        $maxLength = match ($driver) {
            'mysql', 'mariadb' => 64,
            'pgsql' => 63,
            default => null,
        };

        $i = 0;

        do {
            $suffix = $i === 0 ? '_old' : "_old$i";
            $i++;
            $baseLength = $maxLength === null
                ? strlen($table)
                : min(strlen($table), $maxLength - strlen($prefix) - strlen($suffix));

            if ($baseLength < 1) {
                throw new RuntimeException("The `{$prefix}{$table}` table can’t be archived, because its table prefix leaves no room for an `{$suffix}` suffix.");
            }

            $newName = substr($table, 0, $baseLength).$suffix;
        } while (Schema::hasTable($newName));

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach (Schema::getForeignKeys($table) as $foreignKey) {
                    $blueprint->dropForeign($foreignKey['name']);
                }
            });
        }

        Schema::rename($table, $newName);

        $rawTable = $prefix.$table;
        $rawNewName = $prefix.$newName;

        Schema::table($newName, function (Blueprint $blueprint) use ($newName, $rawTable, $rawNewName, $maxLength) {
            foreach (Schema::getIndexes($newName) as $index) {
                if (strtolower($index['name']) === 'primary') {
                    continue;
                }

                $blueprint->renameIndex($index['name'], $this->limitObjectName(str_starts_with($index['name'], "{$rawTable}_")
                    ? $rawNewName.substr($index['name'], strlen($rawTable))
                    : "{$rawNewName}_{$index['name']}", $maxLength));
            }
        });

        if ($driver === 'pgsql' && ($sequence = DB::scalar('select pg_get_serial_sequence(?, ?)', [$rawNewName, 'id']))) {
            DB::statement(sprintf(
                'alter sequence %s rename to %s',
                $sequence,
                DB::getQueryGrammar()->wrap($this->limitObjectName("{$rawNewName}_id_seq", $maxLength)),
            ));
        }
    }

    /**
     * Shortens a database object name to the given length, ending it with a hash of the full name so it stays unique.
     */
    private function limitObjectName(string $name, ?int $maxLength): string
    {
        if ($maxLength === null || strlen($name) <= $maxLength) {
            return $name;
        }

        return substr($name, 0, $maxLength - 9).'_'.substr(md5($name), 0, 8);
    }
}
