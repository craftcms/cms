<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tools;

use Craft;
use craft\app\db\Query;

/**
 * Search Index tool.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SearchIndex extends BaseTool
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app','Rebuild Search Indexes');
	}

	/**
	 * @inheritDoc ToolInterface::getIconValue()
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'search';
	}

	/**
	 * @inheritDoc ToolInterface::performAction()
	 *
	 * @param array $params
	 *
	 * @return array
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
			// Get the element type
			$elementType = Craft::$app->elements->getElementType($params['type']);

			if ($elementType)
			{
				if ($elementType->isLocalized())
				{
					$localeIds = Craft::$app->getI18n()->getSiteLocaleIds();
				}
				else
				{
					$localeIds = [Craft::$app->getI18n()->getPrimarySiteLocaleId()];
				}

				$criteria = Craft::$app->elements->getCriteria($params['type'], [
					'id'            => $params['id'],
					'status'        => null,
					'localeEnabled' => null,
				]);

				foreach ($localeIds as $localeId)
				{
					$criteria->locale = $localeId;
					$element = $criteria->first();

					if ($element)
					{
						Craft::$app->search->indexElementAttributes($element);

						if ($elementType->hasContent())
						{
							$fieldLayout = $element->getFieldLayout();
							$keywords = [];

							foreach ($fieldLayout->getFields() as $fieldLayoutField)
							{
								$field = $fieldLayoutField->getField();

								if ($field)
								{
									$fieldType = $field->getFieldType();

									if ($fieldType)
									{
										$fieldType->element = $element;

										$handle = $field->handle;

										// Set the keywords for the content's locale
										$fieldSearchKeywords = $fieldType->getSearchKeywords($element->getFieldValue($handle));
										$keywords[$field->id] = $fieldSearchKeywords;
									}
								}
							}

							Craft::$app->search->indexElementFields($element->id, $localeId, $keywords);
						}
					}
				}
			}
		}
	}
}
