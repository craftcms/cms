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
        $dateRanges = array(
	        'd7' => array('label' => Craft::t('Last 7 days'), 'startDate' => '-7 days', 'endDate' => null),
	        'd30' => array('label' => Craft::t('Last 30 days'), 'startDate' => '-30 days', 'endDate' => null),
	        'lastweek' => array('label' => Craft::t('Last Week'), 'startDate' => '-2 weeks', 'endDate' => '-1 week'),
	        'lastmonth' => array('label' => Craft::t('Last Month'), 'startDate' => '-2 months', 'endDate' => '-1 month'),
        );

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
		    ->select('DATE_FORMAT(users.dateCreated, "%Y-%m-%d") as date, COUNT(*) as totalUsers')
		    ->from('users users')
		    ->group('YEAR(users.dateCreated), MONTH(users.dateCreated), DAY(users.dateCreated)');

        if($userGroupId)
        {
	        $query->join('usergroups_users userGroupUser', 'users.id=userGroupUser.userId');
	        $query->where('userGroupUser.groupId='.$userGroupId);
        }

	    $results = $query->queryAll();

        $reportDataTable = $this->getNewUsersReportDataTable($startDate, $endDate, $results);
        $scale = $this->getScale($startDate, $endDate);

        foreach($reportDataTable as $row)
        {
            $total = $total + $row[1];
        }

        $response = array(
            'report' => $reportDataTable,
            'orientation' => craft()->locale->getOrientation(),
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

        $columns = array(
	        array('type' => 'date', 'label' => Craft::t('Date')),
	        array('type' => 'number','label' => Craft::t('Users')),
        );


        // fill data table rows from results and set a total of zero users when no result is found for that date

        $rows = array();

        $cursorCurrent = new DateTime($startDate);

        while($cursorCurrent->getTimestamp() < $endDate->getTimestamp())
        {
            $cursorStart = new DateTime($cursorCurrent);
            $cursorCurrent->modify('+1 '.$scale);
            $cursorEnd = $cursorCurrent;

            $row = array(
	            strftime("%Y-%m-%d", $cursorStart->getTimestamp()), // date
	            0 // totalUsers
	        );

            foreach($results as $result)
            {
                if($result['date'] == strftime("%Y-%m-%d", $cursorStart->getTimestamp()))
                {
                    $row = array(
                        $result['date'], // date
                        $result['totalUsers'] // totalUsers
                    );
                }
            }

            $rows[] = $row;
        }

        $chartColumns = array();
        $chartRows = array();

        foreach($columns as $column)
        {
            $chartColumns[] = $column['label'];
        }

        $chartRows = array($chartColumns);
        $chartRows = array_merge($chartRows, array_reverse($rows));

        return $chartRows;
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
