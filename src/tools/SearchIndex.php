<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tools;

use Craft;
use craft\app\base\Tool;
use craft\app\base\ElementInterface;
use craft\app\db\Query;

/**
 * SearchIndex represents a Rebuild Search Indexes tool.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SearchIndex extends Tool
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Rebuild Search Indexes');
	}

	/**
	 * @inheritdoc
	 */
	public static function iconValue()
	{
		return 'search';
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function performAction($params = [])
	{
		if (!empty($params['start']))
		{
			// Truncate the searchindex table
			Craft::$app->getDb()->createCommand()->truncateTable('{{%searchindex}}')->execute();

			// Get all the element IDs ever
			$elements = (new Query())
				->select(['id', 'type'])
				->from('{{%elements}}')
				->all();

			$batch = [];

			foreach ($elements as $element)
			{
				$batch[] = ['params' => $element];
			}

			return [
				'batches' => [$batch]
			];
		}
		else
		{
			/** @var ElementInterface $class */
			$class = $params['type'];

			if ($class::isLocalized())
			{
				$localeIds = Craft::$app->getI18n()->getSiteLocaleIds();
			}
			else
			{
				$localeIds = [Craft::$app->getI18n()->getPrimarySiteLocaleId()];
			}

			$query = $class::find()
				->id($params['id'])
				->status(null)
				->localeEnabled(false);

			foreach ($localeIds as $localeId)
			{
				$query->locale($localeId);
				$element = $query->one();

				if ($element)
				{
					Craft::$app->getSearch()->indexElementAttributes($element);

					if ($class::hasContent())
					{
						$fieldLayout = $element->getFieldLayout();
						$keywords = [];

						foreach ($fieldLayout->getFields() as $field)
						{
							// Set the keywords for the content's locale
							$fieldValue = $element->getFieldValue($field->handle);
							$fieldSearchKeywords = $field->getSearchKeywords($fieldValue, $element);
							$keywords[$field->id] = $fieldSearchKeywords;
						}

						Craft::$app->getSearch()->indexElementFields($element->id, $localeId, $keywords);
					}
				}
			}
		}
	}
}
