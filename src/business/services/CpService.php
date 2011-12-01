<?php

class CpService extends CApplicationComponent implements ICpService
{
	/*
	 * Dashboard
	 */

	/**
	 * Returns the dashboard widget data for the current user
	 * @return array
	 */
	public function getDashboardWidgetData()
	{
		$widgetData = array();

		$widgets = array(
			new UpdatesWidget,
			new RecentActivityWidget,
			new ContentWidget,
			new FeedWidget,
			//new Analytics_ReferrersWidget,
			//new Analytics_KeywordsWidget,
			//new Analytics_PageviewsWidget,
			//new Analytics_ContentWidget,
		);

		foreach ($widgets as $widget)
		{
			$body = $widget->body();
			if ($body !== false)
			{
				$widgetData[] = array(
					'title' => $widget->title,
					'className' => $widget->className,
					'body' => $body,
					'settings' => $widget->settings()
				);
			}
		}

		return $widgetData;
	}
}
