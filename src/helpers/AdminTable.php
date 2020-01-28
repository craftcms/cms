<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

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
        $to = $to > $total ? $total : $to;
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
}
