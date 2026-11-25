<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\GreaterThan;
use Tpetry\QueryExpressions\Operator\Comparison\IsNull;
use Tpetry\QueryExpressions\Operator\Comparison\LessThanOrEqual;
use Tpetry\QueryExpressions\Operator\Logical\CondOr;
use Tpetry\QueryExpressions\Value\Value;

/**
 * Updates entries’ stored statuses, so the first run of `craft:update-element-statuses` doesn’t dispatch
 * `ElementStatusChanged` for entries that went live or expired before the upgrade. Also adds the indexes that the
 * command uses to find entries whose dates passed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = new Value(Query::prepareDateForDb(now()->endOfMinute()));
        $status = new CaseGroup(
            when: [
                new CaseRule(new Value('pending'), new CondOr(new IsNull('postDate'), new GreaterThan('postDate', $now))),
                new CaseRule(new Value('expired'), new LessThanOrEqual('expiryDate', $now)),
            ],
            else: new Value('live'),
        );

        DB::table(Table::ENTRIES)
            ->where('status', '!=', $status)
            ->update(['status' => $status]);

        foreach ([['status', 'postDate'], ['status', 'expiryDate']] as $columns) {
            if (! Schema::hasIndex(Table::ENTRIES, $columns)) {
                Schema::createIndex(Table::ENTRIES, $columns);
            }
        }
    }

    public function down(): void {}
};
