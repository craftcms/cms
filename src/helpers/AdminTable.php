<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\db\Query;
use Exception;
use yii\db\Expression;

/**
 * Admin Table helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
abstract class AdminTable
{
    /**
     * @param int $page
     * @param int $total
     * @param int $limit
     * @return array
     */
    public static function paginationLinks(int $page, int $total, int $limit): array
    {
        $lastPage = ceil($total / $limit);
        $from = ($page * $limit) - $limit;
        $to = $from + $limit;
        $to = min($to, $total);
        $from++;

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => (int)$lastPage,
            'next_page_url' => '?next',
            'prev_page_url' => '?prev',
            'from' => (int)$from,
            'to' => (int)$to,
        ];
    }

    /**
     * @param string $table
     * @param int $id
     * @param int $page
     * @param int $perPage
     * @param string $sortColumn
     * @param array $criteria
     * @return bool
     * @since 4.6.0
     */
    public static function moveToPage(string $table, int $id, int $page, int $perPage, string $sortColumn = 'sortOrder', array $criteria = []): bool
    {
        $lastPage = ceil((new Query())
                ->from([$table])
                ->count() / $perPage);

        if ($page > $lastPage || $page < 1) {
            return false;
        }

        $criteria += [
            'select' => [$sortColumn],
            'from' => [$table],
            'where' => ['id' => $id],
        ];

        $currentSortOrderQuery = new Query();
        $currentSortOrderQuery = Craft::configure($currentSortOrderQuery, $criteria);

        $currentSortOrder = $currentSortOrderQuery->scalar();

        $newSortOrder = ($page - 1) * $perPage + 1;

        if ($currentSortOrder == $newSortOrder) {
            return true;
        }

        $isGoingUp = $newSortOrder > $currentSortOrder;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            if ($isGoingUp) {
                Craft::$app->getDb()->createCommand()
                    ->update($table,
                        [$sortColumn => new Expression('[[' . $sortColumn . ']] - 1')],
                        ['and', ['>', $sortColumn, $currentSortOrder], ['<=', $sortColumn, $newSortOrder]]
                    )
                    ->execute();
            } else {
                Craft::$app->getDb()->createCommand()
                    ->update($table,
                        [$sortColumn => new Expression('[[' . $sortColumn . ']] + 1')],
                        ['and', ['<', $sortColumn, $currentSortOrder], ['>=', $sortColumn, $newSortOrder]]
                    )
                    ->execute();
            }

            Craft::$app->getDb()->createCommand()
                ->update($table, [$sortColumn => $newSortOrder], ['id' => $id])
                ->execute();

            $transaction->commit();
        } catch (Exception) {
            $transaction->rollBack();
            return false;
        }

        return true;
    }
}
