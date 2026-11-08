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
     */
    public function archiveTableIfExists(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $newName = "{$table}_old";

        for ($i = 1; Schema::hasTable($newName); $i++) {
            $newName = "{$table}_old$i";
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach (Schema::getForeignKeys($table) as $foreignKey) {
                    $blueprint->dropForeign($foreignKey['name']);
                }
            });
        }

        Schema::rename($table, $newName);

        $prefix = DB::getTablePrefix();
        $rawTable = $prefix.$table;
        $rawNewName = $prefix.$newName;

        Schema::table($newName, function (Blueprint $blueprint) use ($newName, $rawTable, $rawNewName) {
            foreach (Schema::getIndexes($newName) as $index) {
                if (strtolower($index['name']) === 'primary') {
                    continue;
                }

                $blueprint->renameIndex($index['name'], str_starts_with($index['name'], "{$rawTable}_")
                    ? $rawNewName.substr($index['name'], strlen($rawTable))
                    : "{$rawNewName}_{$index['name']}");
            }
        });

        if ($driver === 'pgsql' && ($sequence = DB::scalar('select pg_get_serial_sequence(?, ?)', [$rawNewName, 'id']))) {
            DB::statement(sprintf(
                'alter sequence %s rename to %s',
                $sequence,
                DB::getQueryGrammar()->wrapTable("{$newName}_id_seq"),
            ));
        }
    }
}
