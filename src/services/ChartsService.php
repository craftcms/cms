<?php
namespace Craft;

/**
 * Charts Service
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class ChartsService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    /**
     * Returns a new users chart report based on a start date, end date, and an optional group ID
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $userGroupId
     *
     * @return array
     */
    public function getNewUsersReport(DateTime $startDate, DateTime $endDate, $userGroupId = null)
    {
        $dataTable = $this->getNewUsersDataTable($startDate, $endDate, $userGroupId);

        $total = 0;

        foreach($dataTable['rows'] as $row)
        {
            $total = $total + $row[1];
        }

        return array(
            'formats' => $this->getFormats(),
            'orientation' => craft()->locale->getOrientation(),
            'report' => $dataTable,
            'scale' => $this->getScale($startDate, $endDate),
            'total' => $total,
        );
    }

    /**
     * Return the scale based on the start date and the end date.
     *
     * @param string $startDate
     * @param string $endDate
     *
     * @return string Can return `hour`, `day`, `month`, or `year`
     */
    public function getScale(DateTime $startDate, DateTime $endDate)
    {
        // auto scale

        $numberOfDays = floor(($endDate->getTimestamp() - $startDate->getTimestamp()) / (60*60*24));

        if ($numberOfDays > (360 * 2))
        {
            $scale = 'year';
        }
        elseif($numberOfDays > 60)
        {
            $scale = 'month';
        }
        elseif($numberOfDays > 2)
        {
            $scale = 'day';
        }
        else
        {
            $scale = 'hour';
        }

        return $scale;
    }

    /**
     * Returns the scale date format based on the scale
     *
     * @param string $scale
     *
     * @return string
     */
    public function getScaleDateFormat($scale)
    {
        switch ($scale)
        {
            case 'year':
                return "%Y-01-01";
                break;
            case 'month':

                return "%Y-%m-01";
                break;

            case 'day':
                return "%Y-%m-%d";
                break;

            case 'hour':
                return "%Y-%m-%d %H:00:00";
                break;
        }
    }

    /**
     * Returns the short date, decimal, percent and currency D3 formats based on Craft's locale settings
     *
     * @return array
     */
    public function getFormats()
    {
        return array(
            'shortDateFormats' => $this->getShortDateFormats(),
            'decimalFormat' => $this->getDecimalFormat(),
            'percentFormat' => $this->getPercentFormat(),
            'currencyFormat' => $this->getCurrencyFormat(),
        );
    }

    /**
     * Returns the D3 short date formats based on Yii's short date format
     *
     * @return array
     */
    public function getShortDateFormats()
    {
        $format = craft()->locale->getDateFormat('short');

        $removals = array(
            'day' => array('y'),
            'month' => array('d'),
            'year' => array('d', 'm'),
        );

        $shortDateFormats = array();

        foreach($removals as $unit => $chars)
        {
            $shortDateFormats[$unit] = $format;

            foreach($chars as $char)
            {
                $shortDateFormats[$unit] = preg_replace("/(^[{$char}]+\W+|\W+[{$char}]+)/i", '', $shortDateFormats[$unit]);
            }
        }


        // yii formats to d3 formats

        $yiiToD3Formats = array(
            'day' => array('dd' => '%d','d' => '%d'),
            'month' => array('MM' => '%m','M' => '%m'),
            'year' => array('yyyy' => '%Y','yy' => '%y','y' => '%y')
        );

        foreach($shortDateFormats as $unit => $format)
        {
            foreach($yiiToD3Formats as $_unit => $_formats)
            {
                foreach($_formats as $yiiFormat => $d3Format)
                {
                    $pattern = "/({$yiiFormat})/i";

                    preg_match($pattern, $shortDateFormats[$unit], $matches);

                    if(count($matches) > 0)
                    {
                        $shortDateFormats[$unit] = preg_replace($pattern, $d3Format, $shortDateFormats[$unit]);

                        break;
                    }

                }
            }
        }

        return $shortDateFormats;
    }

    /**
     * Returns the D3 decimal format based on Yii's decimal format
     *
     * @return array
     */
    public function getDecimalFormat()
    {
        $format = craft()->locale->getDecimalFormat();

        $yiiToD3Formats = array(
            '#,##,##0.###' => ',.3f',
            '#,##0.###' => ',.3f',
            '#0.######' => '.6f',
            '#0.###;#0.###-' => '.3f',
            '0 mil' => ',.3f',
        );

        if(isset($yiiToD3Formats[$format]))
        {
            return $yiiToD3Formats[$format];
        }
    }

    /**
     * Returns the D3 percent format based on Yii's percent format
     *
     * @return array
     */
    public function getPercentFormat()
    {
        $format = craft()->locale->getPercentFormat();

        $yiiToD3Formats = array(
            '#,##,##0%' => ',.2%',
            '#,##0%' => ',.2%',
            '#,##0 %' => ',.2%',
            '#0%' => ',.0%',
            '%#,##0' => ',.2%',
        );

        if(isset($yiiToD3Formats[$format]))
        {
            return $yiiToD3Formats[$format];
        }
    }

    /**
     * Returns the D3 currency format based on Yii's currency format
     *
     * @return array
     */
    public function getCurrencyFormat()
    {
        $format = craft()->locale->getCurrencyFormat();

        $yiiToD3Formats = array(

            '#,##0.00 ¤' => '$,.2f',
            '#,##0.00 ¤;(#,##0.00 ¤)' => '$,.2f',
            '¤#,##0.00' => '$,.2f',
            '¤#,##0.00;(¤#,##0.00)' => '$,.2f',
            '¤#,##0.00;¤-#,##0.00' => '$,.2f',
            '¤#0.00' => '$.2f',
            '¤ #,##,##0.00' => '$,.2f',
            '¤ #,##0.00' => '$,.2f',
            '¤ #,##0.00;¤-#,##0.00' => '$,.2f',
            '¤ #0.00' => '$.2f',
            '¤ #0.00;¤ #0.00-' => '$.2f',
        );

        if(isset($yiiToD3Formats[$format]))
        {
            return $yiiToD3Formats[$format];
        }
    }

    /**
     * Returns the predefined date ranges with their label, start date and end date.
     *
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

    // Private Methods
    // =========================================================================

    /**
     * Returns new users report as a data table
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $userGroupId
     *
     * @return array Returns a data table (array of columns and rows)
     */
    private function getNewUsersDataTable(DateTime $startDate, DateTime $endDate, $userGroupId)
    {
        $query = craft()->db->createCommand()
            ->select('DATE_FORMAT(users.dateCreated, "%Y-%m-%d") as date, COUNT(*) as totalUsers')
            ->from('users users')
            ->where(array('and', 'users.dateCreated > :startDate', 'users.dateCreated < :endDate'), array(':startDate' => $startDate->mySqlDateTime(), ':endDate' => $endDate->mySqlDateTime()));
            ->group('YEAR(users.dateCreated), MONTH(users.dateCreated), DAY(users.dateCreated)');

        if($userGroupId)
        {
            $query->join('usergroups_users userGroupUser', 'users.id=userGroupUser.userId');
            $query->where('userGroupUser.groupId='.$userGroupId);
        }

        $results = $query->queryAll();

        return $this->parseResultsToDataTable($startDate, $endDate, $results);
    }

    /**
     * Parses database results, fills blanks (dates where row value is zero), and then returns a data table
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $results
     *
     * @return array Returns an array of columns and rows
     */
    private function parseResultsToDataTable($startDate, $endDate, $results)
    {
        $columns = array(
	        array('type' => 'date', 'label' => Craft::t('Date')),
	        array('type' => 'number','label' => Craft::t('Users')),
        );


        // fill data table rows from results and set a total of zero users when no result is found for that date

        $scale = $this->getScale($startDate, $endDate);

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

        return array(
            'columns' => $columns,
            'rows' => $rows
        );
    }
}
