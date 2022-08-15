<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\StringHelper;
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
        $query = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::ELEMENTS])
            ->where([
                'in', 'uid', (new Query())
                    ->select(['uid'])
                    ->from([Table::ELEMENTS])
                    ->groupBy(['uid'])
                    ->having('count([[uid]]) > 1'),
            ])
            ->orderBy(['id' => SORT_ASC]);

        $total = $query->count();
        if ($total == 0) {
            $this->stdout('No elements with duplicate UIDs found.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout("Found $total elements with duplicate UIDs." . PHP_EOL);

        foreach (Db::each($query) as $result) {
            if (!isset($uids[$result['uid']])) {
                // This is the first time this UID was issued
                $uids[$result['uid']] = true;
                continue;
            }

            // Duplicate! Give this element a unique UID
            $newUid = StringHelper::UUID();
            $this->stdout("- Changing {$result['uid']} ({$result['id']}) to $newUid ... ");
            Db::update(Table::ELEMENTS, [
                'uid' => $newUid,
            ], [
                'id' => $result['id'],
            ], [], false);
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout('Finished assigning unique UIDs to all elements.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
