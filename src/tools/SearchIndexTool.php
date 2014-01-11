<?php
namespace Craft;

/**
 * Search Index tool
 */
class SearchIndexTool extends BaseTool
{
	/**
	 * Returns the tool name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Rebuild Search Indexes');
	}

	/**
	 * Returns the tool's icon value.
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'search';
	}

	/**
	 * Performs the tool's action.
	 *
	 * @param array $params
	 * @return array
	 */
	public function performAction($params = array())
	{
		if (!empty($params['start']))
		{
			// Get all the element IDs ever
			$elements = craft()->db->createCommand()
				->select('id, type')
				->from('elements')
				->queryAll();

			$batch = array();

			foreach ($elements as $element)
			{
				$batch[] = array('params' => $element);
			}

			return array(
				'batches' => array($batch)
			);
		}
		else
		{
			// Get the element type
			$elementType = craft()->elements->getElementType($params['type']);

			if ($elementType)
			{
				$criteria = craft()->elements->getCriteria($params['type'], array(
					'id' => $params['id'],
					'status' => null,
					'localeEnabled' => null,
				));

				$result = craft()->elements->buildElementsQuery($criteria, $fieldColumns)->queryAll();

				if ($result)
				{
					$contentService = craft()->content;

					foreach ($result as $row)
					{
						if ($elementType->hasContent())
						{
							// Separate the content values from the main element attributes
							$content = array();
							$content['id']        = $row['contentId'];
							$content['elementId'] = $row['id'];
							$content['locale']    = $row['locale'];

							if (isset($row['title']))
							{
								$content['title'] = $row['title'];
								unset($row['title']);
							}

							if ($fieldColumns)
							{
								foreach ($fieldColumns as $column)
								{
									if (isset($row[$column['column']]))
									{
										$content[$column['handle']] = $row[$column['column']];
										unset($row[$column['column']]);
									}
								}
							}
						}

						$element = $elementType->populateElementModel($row);

						// Index the basic element attributes
						craft()->search->indexElementAttributes($element, $element->locale);

						if ($elementType->hasContent())
						{
							$originalContentTable      = $contentService->contentTable;
							$originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
							$originalFieldContext      = $contentService->fieldContext;

							$contentService->contentTable      = $element->getContentTable();
							$contentService->fieldColumnPrefix = $element->getFieldColumnPrefix();
							$contentService->fieldContext      = $element->getFieldContext();

							$element->setContent($content);

							$searchKeywords = array();

							foreach (craft()->fields->getAllFields() as $field)
							{
								$fieldType = $field->getFieldType();

								if ($fieldType)
								{
									$fieldType->element = $element;
									$handle = $field->handle;

									// Set the keywords for the content's locale
									$fieldSearchKeywords = $fieldType->getSearchKeywords($element->$handle);
									$searchKeywords[$field->id] = $fieldSearchKeywords;
								}
							}

							craft()->search->indexElementFields($element->id, $element->locale, $searchKeywords);

							$contentService->contentTable      = $originalContentTable;
							$contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
							$contentService->fieldContext      = $originalFieldContext;
						}
					}
				}
			}
		}
	}
}
