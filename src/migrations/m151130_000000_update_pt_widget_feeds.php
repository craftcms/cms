<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151130_000000_update_pt_widget_feeds extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$results = craft()->db->createCommand()
			->select('settings, id')
			->from('widgets')
			->where('type = :type', array(':type' => 'Feed'))
			->queryAll();

		foreach ($results as $result)
		{
			$settings = JsonHelper::decode($result['settings']);

			if (isset($settings['url']) && $settings['url'] == 'http://feeds.feedburner.com/blogandtonic')
			{
				Craft::log('Updating Feeds widget setting to new craftcms.com URL', LogLevel::Info, true);
				$settings['url'] = 'https://craftcms.com/news.rss';
				$settings['title'] = 'Craft News';

				$settings = JsonHelper::encode($settings);

				craft()->db->createCommand()->update(
					'widgets',
					array('settings' => $settings),
					'id = :id',
					array(':id' => $result['id'])
				);
			}
		}

		return true;
	}
}
