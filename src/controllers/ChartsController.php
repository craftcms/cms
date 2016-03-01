<?php
namespace Craft;

/**
 * Charts Controller
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class ChartsController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * Get Revenue Report
     *
     * @return null
     */
    public function actionGetNewUsersReport()
    {
        $dateRange = craft()->request->getParam('dateRange');
        $userGroupId = craft()->request->getParam('userGroupId');

        $startDate = false;
        $endDate = false;

        $dateRanges = craft()->charts->getDateRanges();

        if(!empty($dateRanges[$dateRange]))
        {
            $startDate = $dateRanges[$dateRange]['startDate'];
            $endDate = $dateRanges[$dateRange]['endDate'];
        }

        $startDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);
        $endDate->modify('+1 day');

        $revenueReport = craft()->charts->getNewUsersReport($startDate, $endDate, $userGroupId);

        $this->returnJson($revenueReport);
    }
}
