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
	 * Returns the data needed to display a New Users chart.
	 *
	 * @return void
	 */
	public function actionGetNewUsersData()
	{
		$userGroupId = craft()->request->getRequiredPost('userGroupId');
		$startDateParam = craft()->request->getRequiredPost('startDate');
		$endDateParam = craft()->request->getRequiredPost('endDate');

		$startDate = DateTime::createFromString($startDateParam);
		$endDate = DateTime::createFromString($endDateParam);

		// Start at midnight on the start date, end at midnight after the end date
		$startDate = new DateTime($startDate->format(DateTime::W3C_DATE), new \DateTimeZone(craft()->timezone));
		$endDate = new DateTime($endDate->modify('+1 day')->format(DateTime::W3C_DATE), new \DateTimeZone(craft()->timezone));

		$intervalUnit = 'day';

		// Prep the query
		$query = craft()->db->createCommand()
			->from('users users')
			->select('COUNT(*) as value');

		if ($userGroupId)
		{
			$query->join('usergroups_users userGroupUsers', 'userGroupUsers.userId = users.id');
			$query->where('userGroupUsers.groupId = :userGroupId', array(':userGroupId' => $userGroupId));
		}

		// Get the chart data table
		$dataTable = ChartHelper::getRunChartDataFromQuery($query, $startDate, $endDate, 'users.dateCreated', array(
			'intervalUnit' => $intervalUnit,
			'valueLabel' => Craft::t('New Users'),
		));

		// Get the total number of new users
		$total = 0;

		foreach($dataTable['rows'] as $row)
		{
			$total = $total + $row[1];
		}

		// Return everything
		$this->returnJson(array(
			'dataTable' => $dataTable,
			'total' => $total,

			'formats' => ChartHelper::getFormats(),
			'orientation' => craft()->locale->getOrientation(),
			'scale' => $intervalUnit,
		));
	}
}
