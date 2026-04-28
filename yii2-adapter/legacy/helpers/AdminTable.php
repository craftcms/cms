<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\db\Query;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Typecast;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use yii\db\Expression;

/**
 * Admin Table helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 * @deprecated 6.0.0 use Laravel Pagination instead.
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
        $pageParam = Cms::config()->getPageTriggerParam();
        $paginator = new LengthAwarePaginator(
            items: [],
            total: $total,
            perPage: $limit,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'pageName' => $pageParam,
            ],
        );

        $paginator->appends(request()->except($pageParam));

        $from = null;
        $to = null;

        if ($total > 0) {
            $from = (($page - 1) * $limit) + 1;
            $to = min($from + $limit - 1, $total);
        }

        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'from' => $from,
            'to' => $to,
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
        $currentSortOrderQuery = Typecast::configure($currentSortOrderQuery, $criteria);

        $currentSortOrder = $currentSortOrderQuery->scalar();

        $newSortOrder = ($page - 1) * $perPage + 1;

        if ($currentSortOrder == $newSortOrder) {
            return true;
        }

        $isGoingUp = $newSortOrder > $currentSortOrder;

        DB::beginTransaction();
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

            DB::commit();
        } catch (Exception) {
            DB::rollBack();
            return false;
        }

        return true;
    }
}
