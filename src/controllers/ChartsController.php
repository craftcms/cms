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
        $userGroupId = craft()->request->getRequiredPost('userGroupId');
        $startDateParam = craft()->request->getRequiredPost('startDate');
        $endDateParam = craft()->request->getRequiredPost('endDate');

        $startDate = DateTime::createFromString($startDateParam, craft()->timezone);
        $endDate = DateTime::createFromString($endDateParam, craft()->timezone);
        $endDate->modify('+1 day');

        $revenueReport = craft()->charts->getNewUsersReport($startDate, $endDate, $userGroupId);

        $this->returnJson($revenueReport);
    }
}
