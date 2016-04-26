<?php
namespace Craft;

/**
 * The base class for all Craft element types. Any element type must extend this class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.elementtypes
 * @since     1.0
 */
abstract class BaseElementType extends BaseComponentType implements IElementType
{
	// Properties
	// =========================================================================

	/**
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'ElementType';

	/**
	 * @var
	 */
	private $_sourcesByContext;

	// Public Methods
	// =========================================================================

	// Basic info methods
	// -------------------------------------------------------------------------

	/**
	 * @inheritDoc IElementType::hasContent()
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::hasTitles()
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::isLocalized()
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::hasStatuses()
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::getStatuses()
	 *
	 * @return array|null
	 */
	public function getStatuses()
	{
		return array(
			BaseElementModel::ENABLED => Craft::t('Enabled'),
			BaseElementModel::DISABLED => Craft::t('Disabled')
		);
	}

	/**
	 * @inheritDoc IElementType::getSources()
	 *
	 * @param null $context
	 *
	 * @return array|bool|false
	 */
	public function getSources($context = null)
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::getSource()
	 *
	 * @param string $key
	 * @param null   $context
	 *
	 * @return array|null
	 */
	public function getSource($key, $context = null)
	{
		$contextKey = ($context ? $context : '*');

		if (!isset($this->_sourcesByContext[$contextKey]))
		{
			$this->_sourcesByContext[$contextKey] = $this->getSources($context);
		}

		return $this->_findSource($key, $this->_sourcesByContext[$contextKey]);
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		return array();
	}

	/**
	 * @inheritDoc IElementType::defineSearchableAttributes()
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array();
	}

	// Element index methods
	// -------------------------------------------------------------------------

	/**
	 * @inheritDoc IElementType::getIndexHtml()
	 *
	 * @param ElementCriteriaModel $criteria
	 * @param array                $disabledElementIds
	 * @param array                $viewState
	 * @param null|string          $sourceKey
	 * @param null|string          $context
	 * @param bool                 $includeContainer
	 * @param bool                 $showCheckboxes
	 *
	 * @return string
	 */
	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes)
	{
		$variables = array(
			'viewMode'            => $viewState['mode'],
			'context'             => $context,
			'elementType'         => new ElementTypeVariable($this),
			'disabledElementIds'  => $disabledElementIds,
			'collapsedElementIds' => craft()->request->getParam('collapsedElementIds'),
			'showCheckboxes'      => $showCheckboxes,
		);

		// Special case for sorting by structure
		if (isset($viewState['order']) && $viewState['order'] == 'structure')
		{
			$source = $this->getSource($sourceKey, $context);

			if (isset($source['structureId']))
			{
				$criteria->order = 'lft asc';
				$variables['structure'] = craft()->structures->getStructureById($source['structureId']);

				// Are they allowed to make changes to this structure?
				if ($context == 'index' && $variables['structure'] && !empty($source['structureEditable']))
				{
					$variables['structureEditable'] = true;

					// Let StructuresController know that this user can make changes to the structure
					craft()->userSession->authorize('editStructure:'.$variables['structure']->id);
				}
			}
			else
			{
				unset($viewState['order']);
			}
		}
		else if (!empty($viewState['order']) && $viewState['order'] == 'score')
		{
			$criteria->order = 'score';
		}
		else
		{
			$sortableAttributes = $this->defineSortableAttributes();

			if ($sortableAttributes)
			{
				$order = (!empty($viewState['order']) && isset($sortableAttributes[$viewState['order']])) ? $viewState['order'] : ArrayHelper::getFirstKey($sortableAttributes);
				$sort  = (!empty($viewState['sort']) && in_array($viewState['sort'], array('asc', 'desc'))) ? $viewState['sort'] : 'asc';

				// Combine them, accounting for the possibility that $order could contain multiple values,
				// and be defensive about the possibility that the first value actually has "asc" or "desc"

				// typeId             => typeId [sort]
				// typeId, title      => typeId [sort], title
				// typeId, title desc => typeId [sort], title desc
				// typeId desc        => typeId [sort]

				$criteria->order = preg_replace('/^(.*?)(?:\s+(?:asc|desc))?(,.*)?$/i', "$1 {$sort}$2", $order);
			}
		}

		switch ($viewState['mode'])
		{
			case 'table':
			{
				// Get the table columns
				$variables['attributes'] = $this->getTableAttributesForSource($sourceKey);

				// Give each attribute a chance to modify the criteria
				foreach ($variables['attributes'] as $attribute)
				{
					$this->prepElementCriteriaForTableAttribute($criteria, $attribute[0]);
				}

				break;
			}
		}

		$variables['elements'] = $criteria->find();

		$template = '_elements/'.$viewState['mode'].'view/'.($includeContainer ? 'container' : 'elements');
		return craft()->templates->render($template, $variables);
	}

	/**
	 * @inheritDoc IElementType::defineSortableAttributes()
	 *
	 * @return array
	 */
	public function defineSortableAttributes()
	{
		$tableAttributes = craft()->elementIndexes->getAvailableTableAttributes($this->getClassHandle());
		$sortableAttributes = array();

		foreach ($tableAttributes as $key => $labelInfo)
		{
			$sortableAttributes[$key] = $labelInfo['label'];
		}

		return $sortableAttributes;
	}

	/**
	 * @inheritDoc IElementType::defineAvailableTableAttributes()
	 *
	 * @return array
	 */
	public function defineAvailableTableAttributes()
	{
		if (method_exists($this, 'defineTableAttributes'))
		{
			// Classic.
			return $this->defineTableAttributes();
		}

		return array();
	}

	/**
	 * @inheritDoc IElementType::getDefaultTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function getDefaultTableAttributes($source = null)
	{
		if (method_exists($this, 'defineTableAttributes'))
		{
			// Classic.
			$availableTableAttributes = $this->defineTableAttributes($source);
		}
		else
		{
			$availableTableAttributes = $this->defineAvailableTableAttributes();
		}

		return array_keys($availableTableAttributes);
	}

	/**
	 * @inheritDoc IElementType::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return mixed|string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		switch ($attribute)
		{
			case 'link':
			{
				$url = $element->getUrl();

				if ($url)
				{
					return '<a href="'.$url.'" target="_blank" data-icon="world" title="'.Craft::t('Visit webpage').'"></a>';
				}
				else
				{
					return '';
				}
			}

			case 'uri':
			{
				$url = $element->getUrl();

				if ($url)
				{
					$value = $element->uri;

					if ($value == '__home__')
					{
						$value = '<span data-icon="home" title="'.Craft::t('Homepage').'"></span>';
					}
					else
					{
						// Add some <wbr> tags in there so it doesn't all have to be on one line
						$find = array('/');
						$replace = array('/<wbr>');

						$wordSeparator = craft()->config->get('slugWordSeparator');

						if ($wordSeparator)
						{
							$find[] = $wordSeparator;
							$replace[] = $wordSeparator.'<wbr>';
						}

						$value = str_replace($find, $replace, $value);
					}

					return '<a href="'.$url.'" target="_blank" class="go" title="'.Craft::t('Visit webpage').'"><span dir="ltr">'.$value.'</span></a>';
				}
				else
				{
					return '';
				}
			}

			default:
			{
				// Is this a custom field?
				if (preg_match('/^field:(\d+)$/', $attribute, $matches))
				{
					$fieldId = $matches[1];
					$field = craft()->fields->getFieldById($fieldId);

					if ($field)
					{
						$fieldType = $field->getFieldType();

						if ($fieldType && $fieldType instanceof IPreviewableFieldType)
						{
							// Was this field value eager-loaded?
							if ($fieldType instanceof IEagerLoadingFieldType && $element->hasEagerLoadedElements($field->handle))
							{
								$value = $element->getEagerLoadedElements($field->handle);
							}
							else
							{
								$value = $element->getFieldValue($field->handle);
							}

							$fieldType->setElement($element);

							return $fieldType->getTableAttributeHtml($value);
						}
					}

					return '';
				}

				$value = $element->$attribute;

				if ($value instanceof DateTime)
				{
					return '<span title="'.$value->localeDate().' '.$value->localeTime().'">'.$value->uiTimestamp().'</span>';
				}

				return HtmlHelper::encode($value);
			}
		}
	}

	/**
	 * @inheritDoc IElementType::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array();
	}

	// Methods for customizing the content table
	// -----------------------------------------------------------------------------

	/**
	 * @inheritDoc IElementType::getContentTableForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return false|string
	 */
	public function getContentTableForElementsQuery(ElementCriteriaModel $criteria)
	{
		return 'content';
	}

	/**
	 * @inheritDoc IElementType::getFieldsForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return FieldModel[]
	 */
	public function getFieldsForElementsQuery(ElementCriteriaModel $criteria)
	{
		$contentService = craft()->content;
		$originalFieldContext = $contentService->fieldContext;
		$contentService->fieldContext = 'global';

		$fields = craft()->fields->getAllFields();

		$contentService->fieldContext = $originalFieldContext;

		return $fields;
	}

	/**
	 * @inheritDoc IElementType::getContentFieldColumnsForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @deprecated Deprecated in 2.3. Element types should implement {@link getFieldsForElementsQuery()} instead.
	 * @return array
	 */
	public function getContentFieldColumnsForElementsQuery(ElementCriteriaModel $criteria)
	{
		$columns = array();
		$fields = $this->getFieldsForElementsQuery($criteria);

		foreach ($fields as $field)
		{
			if ($field->hasContentColumn())
			{
				$columns[] = array(
					'handle' => $field->handle,
					'column' => ($field->columnPrefix ? $field->columnPrefix : 'field_') . $field->handle
				);
			}
		}

		return $columns;
	}

	// Methods for customizing ElementCriteriaModel's for this element type...
	// -------------------------------------------------------------------------

	/**
	 * @inheritDoc IElementType::getElementQueryStatusCondition()
	 *
	 * @param DbCommand $query
	 * @param string    $status
	 *
	 * @return false|string|void
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status)
	{
	}

	/**
	 * @inheritDoc IElementType::modifyElementsQuery()
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return false|null|void
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
	}

	// Element methods

	/**
	 * @inheritDoc IElementType::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return BaseElementModel|void
	 */
	public function populateElementModel($row)
	{
	}

	/**
	 * @inheritDoc IElementType::getEagerLoadingMap()
	 *
	 * @param BaseElementModel[]  $sourceElements
	 * @param string $handle
	 *
	 * @return array|false
	 */
	public function getEagerLoadingMap($sourceElements, $handle)
	{
		// Is $handle a custom field handle?
		// (Leave it up to the extended class to set the field context, if it shouldn't be 'global')
		$field = craft()->fields->getFieldByHandle($handle);

		if ($field)
		{
			$fieldType = $field->getFieldType();

			if ($fieldType && $fieldType instanceof IEagerLoadingFieldType)
			{
				return $fieldType->getEagerLoadingMap($sourceElements);
			}
		}

		return false;
	}

	/**
	 * @inheritDoc IElementType::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		$html = '';

		$fieldLayout = $element->getFieldLayout();

		if ($fieldLayout)
		{
			$originalNamespace = craft()->templates->getNamespace();
			$namespace = craft()->templates->namespaceInputName('fields', $originalNamespace);
			craft()->templates->setNamespace($namespace);

			foreach ($fieldLayout->getFields() as $fieldLayoutField)
			{
				$fieldHtml = craft()->templates->render('_includes/field', array(
					'element'  => $element,
					'field'    => $fieldLayoutField->getField(),
					'required' => $fieldLayoutField->required
				));

				$html .= craft()->templates->namespaceInputs($fieldHtml, 'fields');
			}

			craft()->templates->setNamespace($originalNamespace);
		}

		return $html;
	}

	/**
	 * @inheritDoc IElementType::saveElement()
	 *
	 * @param BaseElementModel $element
	 * @param array            $params
	 *
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $params)
	{
		return craft()->elements->saveElement($element);
	}

	/**
	 * @inheritDoc IElementType::routeRequestForMatchedElement()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return bool|mixed
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element)
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::onAfterMoveElementInStructure()
	 *
	 * @param BaseElementModel $element
	 * @param int              $structureId
	 *
	 * @return null|void
	 */
	public function onAfterMoveElementInStructure(BaseElementModel $element, $structureId)
	{
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the attributes that should be shown for the given source.
	 *
	 * @param string $sourceKey The source key
	 *
	 * @return array The attributes that should be shown for the given source
	 */
	protected function getTableAttributesForSource($sourceKey)
	{
		return craft()->elementIndexes->getTableAttributes($this->getClassHandle(), $sourceKey);
	}

	/**
	 * Preps the element criteria for a given table attribute
	 *
	 * @param ElementCriteriaModel $criteria
	 * @param string               $attribute
	 *
	 * @return void
	 */
	protected function prepElementCriteriaForTableAttribute(ElementCriteriaModel $criteria, $attribute)
	{
		// Is this a custom field?
		if (preg_match('/^field:(\d+)$/', $attribute, $matches))
		{
			$fieldId = $matches[1];
			$field = craft()->fields->getFieldById($fieldId);

			if ($field)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType && $fieldType instanceof IEagerLoadingFieldType)
				{
					$with = $criteria->with ?: array();
					$with[] = $field->handle;
					$criteria->with = $with;
				}
			}
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Finds a source by its key, even if it's nested.
	 *
	 * @param array  $sources
	 * @param string $key
	 *
	 * @return array|null
	 */
	private function _findSource($key, $sources)
	{
		if (isset($sources[$key]))
		{
			return $sources[$key];
		}
		else
		{
			// Look through any nested sources
			foreach ($sources as $source)
			{
				if (!empty($source['nested']) && ($nestedSource = $this->_findSource($key, $source['nested'])))
				{
					return $nestedSource;
				}
			}
		}
	}
}
