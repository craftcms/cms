<?php

namespace CraftCms\Cms\Database\Migrations;

use Illuminate\Database\Migrations\Migration as LaravelMigration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;
use yii\db\MigrationInterface;

/** @since 6.0.0 */
abstract class Migration extends LaravelMigration implements MigrationInterface
{
    protected ConsoleOutput $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput;
    }

    /**
     * @param  string  $table  the table that the new index will be created for. The table name will be properly quoted by the method.
     * @param  string|array|Expression  $columns  the column(s) that should be included in the index. If there are multiple columns, please separate them
     *                                            by commas or use an array. Each column name will be properly quoted by the method. Quoting will be skipped for columns that
     *                                            are passed as an \Illuminate\Database\Query\Expression.
     * @param  string|null  $name  the name of the index. The name will be properly quoted by the method. If null, a name will be automatically generated.
     * @param  bool  $unique  whether to add UNIQUE constraint on the created index.
     */
    public function createIndex(string $table, array|string|Expression $columns, ?string $name = null, bool $unique = false): void
    {
        Schema::table($table, function (Blueprint $table) use ($name, $columns, $unique) {
            $name ??= $this->generateIndexName($table->getTable(), $columns);

            if ($unique) {
                $table->index($columns, $name);

                return;
            }

            $table->index($columns, $name);
        });
    }

    /**
     * @param  string  $table  the table that the foreign key constraint will be added to.
     * @param  string|array  $columns  the name of the column to that the constraint will be added on. If there are multiple columns, separate them with commas or use an array.
     * @param  string  $refTable  the table that the foreign key references to.
     * @param  string|array  $refColumns  the name of the column that the foreign key references to. If there are multiple columns, separate them with commas or use an array.
     * @param  string|null  $name  the name of the foreign key constraint. If null, a name will be automatically generated.
     * @param  string|null  $onDelete  the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @param  string|null  $onUpdate  the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     */
    public function addForeignKey(
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        ?string $name = null,
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): void {
        Schema::table($table, function (Blueprint $table) use ($name, $columns, $refTable, $refColumns, $onDelete, $onUpdate) {
            $table->foreign($columns, $name)
                ->references($refColumns)
                ->on($refTable)
                ->when($onDelete, fn (ForeignKeyDefinition $t) => $t->onDelete($onDelete))
                ->when($onUpdate, fn (ForeignKeyDefinition $t) => $t->onUpdate($onUpdate));
        });
    }

    private function generateIndexName(string $table, array|string|Expression $columns): string
    {
        $connection = DB::connection($this->connection);

        if ($connection->getConfig('prefix_indexes')) {
            $table = str_contains($table, '.')
                ? substr_replace($table, '.'.$connection->getTablePrefix(), strrpos($table, '.'), 1)
                : $connection->getTablePrefix().$table;
        }

        $index = strtolower($table.'_'.implode('_', $columns));

        return 'idx_'.md5($index);
    }
}
