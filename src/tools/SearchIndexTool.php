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
					'status' => null
				));

				$query = craft()->elements->buildElementsQuery($criteria)->queryAll();

				if ($query)
				{
					$fields = craft()->fields->getAllFields();

					foreach ($query as $row)
					{
						// The locale column might be null since the element_i18n table was left-joined into the query,
						// In that case it should be removed from the $row array so that the default value can be used.
						if (!$row['locale'])
						{
							unset($row['locale']);
						}

						// Populate the actual element model
						$element = $elementType->populateElementModel($row);

						// Index the basic element attributes
						craft()->search->indexElementAttributes($element, $element->locale);

						// Index the content keywords
						$searchKeywords = array();

						foreach ($fields as $field)
						{
							$fieldType = craft()->fields->populateFieldType($field);

							if ($fieldType)
							{
								$fieldType->element = $element;

								// Get the field's search keywords
								$handle = $field->handle;
								$fieldSearchKeywords = $fieldType->getSearchKeywords($element->$handle);
								$searchKeywords[$field->id] = $fieldSearchKeywords;
							}
						}

						craft()->search->indexElementFields($element->id, $element->locale, $searchKeywords);
					}
				}
			}
		}
	}
}
