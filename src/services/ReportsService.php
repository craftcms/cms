<?php
namespace Craft;

/**
 * Reports Service
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class ReportsService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function getDateRanges()
    {
        $dateRanges = [
            'd7' => ['label' => 'Last 7 days', 'startDate' => '-7 days', 'endDate' => null],
            'd30' => ['label' => 'Last 30 days', 'startDate' => '-30 days', 'endDate' => null],
            'lastweek' => ['label' => 'Last Week', 'startDate' => '-2 weeks', 'endDate' => '-1 week'],
            'lastmonth' => ['label' => 'Last Month', 'startDate' => '-2 months', 'endDate' => '-1 month'],
        ];

        return $dateRanges;
    }

    /**
     * @param string $startDate
     * @param string $endDate
     * @param int|null $userGroupId
     *
     * @return array
     */
    public function getNewUsersReport($startDate, $endDate, $userGroupId = null)
    {
        $total = 0;

	    $query = craft()->db->createCommand()
		    ->select('DATE_FORMAT(dateCreated, "%d-%b-%y") as date, COUNT(*) as totalUsers')
		    ->from('users')
		    ->group('YEAR(dateCreated), MONTH(dateCreated), DAY(dateCreated)');

        if($userGroupId)
        {
	        $query->where('userGroupUser.groupId='.$userGroupId);
        }

	    $results = $query->queryAll();

        $reportDataTable = $this->getNewUsersReportDataTable($startDate, $endDate, $results);
        $scale = $this->getScale($startDate, $endDate);

        foreach($reportDataTable['rows'] as $row)
        {
            $total = $total + $row[1];
        }

        $response = array(
            'reportDataTable' => $reportDataTable,
            'scale' => $scale,
            'total' => $total,
        );

        return $response;
    }

    /**
     * @param string $startDate
     * @param string $endDate
     * @param array $results
     *
     * @return array
     */
    private function getNewUsersReportDataTable($startDate, $endDate, $results)
    {
        $scale = $this->getScale($startDate, $endDate);

        // columns

        $columns = [
            ['type' => 'date', 'label' => 'Date'],
            ['type' => 'number','label' => 'Users'],
        ];


        // fill data table rows from results and set a total of zero users when no result is found for that date

        $rows = [];

        $cursorCurrent = new DateTime($startDate);

        while($cursorCurrent->getTimestamp() < $endDate->getTimestamp())
        {
            $cursorStart = new DateTime($cursorCurrent);
            $cursorCurrent->modify('+1 '.$scale);
            $cursorEnd = $cursorCurrent;

            $row = [
                strftime("%e-%b-%y", $cursorStart->getTimestamp()), // date
                0 // totalUsers
            ];

            foreach($results as $result)
            {
                if($result['date'] == strftime("%e-%b-%y", $cursorStart->getTimestamp()))
                {
                    $row = [
                        $result['date'], // date
                        $result['totalUsers'] // totalUsers
                    ];
                }
            }

            $rows[] = $row;
        }

        return [
            'columns' => $columns,
            'rows' => $rows
        ];
    }

    /**
     * @param string $startDate
     * @param string $endDate
     *
     * @return string
     */
    public function getScale($startDate, $endDate)
    {
        // auto scale

        $numberOfDays = floor(($endDate->getTimestamp() - $startDate->getTimestamp()) / (60*60*24));

        if ($numberOfDays > 360)
        {
            $scale = 'year';
        }
        elseif($numberOfDays > 60)
        {
            $scale = 'month';
        }
        else
        {
            $scale = 'day';
        }

        return $scale;
    }
}
