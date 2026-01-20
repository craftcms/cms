<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard\Widgets;

use craft\helpers\ChartHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Translation\I18N;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

use function CraftCms\Cms\t;

final readonly class NewUsersController
{
    public function data(Request $request, I18N $i18N): JsonResponse
    {
        $request->validate([
            'userGroupId' => ['nullable', 'integer'],
            'startDate' => ['required', 'integer'],
            'endDate' => ['required', 'integer'],
        ]);

        $startDate = $request->date('startDate');
        $endDate = $request->date('endDate');

        abort_if(is_null($startDate) || is_null($endDate), 400, 'Invalid date range.');

        // Start at midnight on the start date, end at midnight after the end date
        $startDate->startOfDay();
        $endDate->addDay()->startOfDay();

        $intervalUnit = 'day';

        // Prep the query
        $query = DB::table(Table::USERS)->when(
            $request->input('userGroupId'),
            fn (Builder $query) => $query
                ->join(new Alias(Table::USERGROUPS_USERS, 'usergroups_users'), 'usergroups_users.userId', 'users.id')
                ->where('usergroups_users.groupId', $request->integer('userGroupId')),
        );

        // Get the chart data table
        $dataTable = ChartHelper::getRunChartDataFromQuery($query, $startDate, $endDate, 'users.dateCreated', 'count', '*', [
            'intervalUnit' => $intervalUnit,
            'valueLabel' => t('New {type}', [
                'type' => User::pluralDisplayName(),
            ]),
        ]);

        // Get the total number of new users
        $total = new Collection($dataTable['rows'])->sum(fn ($row) => $row[1]);

        // Return everything
        return new JsonResponse([
            'dataTable' => $dataTable,
            'total' => $total,
            'formats' => ChartHelper::formats(),
            'orientation' => $i18N->getLocale()->getOrientation(),
            'scale' => $intervalUnit,
        ]);
    }
}
