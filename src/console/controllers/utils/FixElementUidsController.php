<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use craft\console\Controller;
use craft\helpers\Console;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;
use yii\console\ExitCode;

/**
 * Utilities
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.8
 */
class FixElementUidsController extends Controller
{
    /**
     * Ensures all elements UIDs are unique.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $uids = [];

        $query = DB::table(Table::ELEMENTS)
            ->select(['id', 'uid'])
            ->whereIn('uid', DB::table(Table::ELEMENTS)
                ->select('uid')
                ->groupBy('uid')
                ->havingRaw('count(uid) > 1')
            )
            ->orderBy('id');

        $total = $query->count();
        if ($total === 0) {
            $this->stdout('No elements with duplicate UIDs found.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout("Found $total elements with duplicate UIDs." . PHP_EOL);

        $query->each(function(object $result) use (&$uids) {
            if (!isset($uids[$result->uid])) {
                // This is the first time this UID was issued
                $uids[$result->uid] = true;
                return;
            }

            // Duplicate! Give this element a unique UID
            $newUid = Str::uuid()->toString();
            $this->stdout("- Changing {$result->uid} ({$result->id}) to $newUid ... ");

            DB::table(Table::ELEMENTS)
                ->where('id', $result->id)
                ->update(['uid' => $newUid]);

            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        });

        $this->stdout('Finished assigning unique UIDs to all elements.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
