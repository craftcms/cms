<?php

class CpService extends CApplicationComponent implements ICpService
{
	/*
	 * Dashboard
	 */

	/**
	 * Returns the dashboard widgets for the current user
	 * @return array
	 */
	public function getDashboardWidgets()
	{
		return array(
			new UpdatesWidget,
			new RecentActivityWidget,
			new ContentWidget,
			new FeedWidget,
			//new Analytics_ReferrersWidget,
			//new Analytics_KeywordsWidget,
			//new Analytics_PageviewsWidget,
			//new Analytics_ContentWidget,
		);
	}
}
