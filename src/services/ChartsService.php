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
    private function getNewUsersDataTable(DateTime $startDate, DateTime $endDate, $userGroupId = null)
    {

    }
}
