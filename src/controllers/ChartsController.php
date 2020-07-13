<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ChartHelper;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use yii\base\Exception;
use yii\base\Response;

/**
 * The ChartsController class is a controller that handles charts related operations such as preparing and returning data,
 * in a format ready to being displayed by Craft charts.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ChartsController extends Controller
{
    /**
     * Returns the data needed to display a New Users chart.
     *
     * @return Response
     * @throws Exception
     */
    public function actionGetNewUsersData(): Response
    {
        $userGroupId = $this->request->getRequiredBodyParam('userGroupId');
        $startDateParam = $this->request->getRequiredBodyParam('startDate');
        $endDateParam = $this->request->getRequiredBodyParam('endDate');

        $startDate = DateTimeHelper::toDateTime($startDateParam);
        $endDate = DateTimeHelper::toDateTime($endDateParam);

        if ($startDate === false || $endDate === false) {
            throw new Exception('There was a problem calculating the start and end dates');
        }

        // Start at midnight on the start date, end at midnight after the end date
        $timeZone = new \DateTimeZone(Craft::$app->getTimeZone());
        $startDate = new \DateTime($startDate->format('Y-m-d'), $timeZone);
        $endDate = new \DateTime($endDate->modify('+1 day')->format('Y-m-d'), $timeZone);

        $intervalUnit = 'day';

        // Prep the query
        $query = (new Query())
            ->from(['users' => Table::USERS]);

        if ($userGroupId) {
            $query->innerJoin(['usergroups_users' => Table::USERGROUPS_USERS], '[[usergroups_users.userId]] = [[users.id]]');
            $query->where(['usergroups_users.groupId' => $userGroupId]);
        }

        // Get the chart data table
        $dataTable = ChartHelper::getRunChartDataFromQuery($query, $startDate, $endDate, 'users.dateCreated', 'count', '*', [
            'intervalUnit' => $intervalUnit,
            'valueLabel' => Craft::t('app', 'New Users'),
        ]);

        // Get the total number of new users
        $total = 0;

        foreach ($dataTable['rows'] as $row) {
            $total += $row[1];
        }

        // Return everything
        return $this->asJson([
            'dataTable' => $dataTable,
            'total' => $total,

            'formats' => ChartHelper::formats(),
            'orientation' => Craft::$app->getLocale()->getOrientation(),
            'scale' => $intervalUnit,
        ]);
    }
}
