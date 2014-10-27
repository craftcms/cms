<?php
namespace Craft;

/**
 * The base class for all Craft element types. Any element type must extend this class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
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
	 *
	 * @return string
	 */
	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context)
	{
		$variables = array(
			'viewMode'            => $viewState['mode'],
			'context'             => $context,
			'elementType'         => new ElementTypeVariable($this),
			'disabledElementIds'  => $disabledElementIds,
		);

		switch ($viewState['mode'])
		{
			case 'table':
			{
				// Make sure the attribute is actually allowed
				$variables['attributes'] = $this->defineTableAttributes($sourceKey);

				// Ordering by an attribute?
				if (!empty($viewState['order']) && in_array($viewState['order'], array_keys($variables['attributes'])))
				{
					$criteria->order = $viewState['order'].' '.$viewState['sort'];
					$variables['order'] = $viewState['order'];
					$variables['sort'] = $viewState['sort'];
				}

				break;
			}

			case 'structure':
			{
				$source = $this->getSource($sourceKey, $context);

				$variables['structure']           = craft()->structures->getStructureById($source['structureId']);
				$variables['collapsedElementIds'] = isset($viewState['collapsedElementIds']) ? $viewState['collapsedElementIds'] : array();
				$variables['newChildUrl']         = (isset($source['newChildUrl']) ? $source['newChildUrl'] : null);

				$criteria->offset = 0;
				$criteria->limit = null;

				break;
			}
		}

		$variables['elements'] = $criteria->find();

		$template = '_elements/'.$viewState['mode'].'view/'.(!$criteria->offset ? 'container' : 'elements');
		return craft()->templates->render($template, $variables);
	}

	/**
	 * @inheritDoc IElementType::defineTableAttributes()
	 *
	 * @param null $source
	 *
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array();
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

					return '<a href="'.$url.'" target="_blank" class="go"><span dir="ltr">'.$value.'</span></a>';
				}
				else
				{
					return '';
				}
			}
			case 'dateCreated':
			case 'dateUpdated':
			{
				$date = $element->$attribute;

				if ($date)
				{
					return $date->localeDate();
				}
				else
				{
					return '';
				}
			}
			default:
			{
				return HtmlHelper::encode($element->$attribute);
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
	 * @inheritDoc IElementType::getContentFieldColumnsForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return array
	 */
	public function getContentFieldColumnsForElementsQuery(ElementCriteriaModel $criteria)
	{
		$contentService = craft()->content;
		$columns = array();

		$originalFieldContext = $contentService->fieldContext;
		$contentService->fieldContext = 'global';

		foreach (craft()->fields->getFieldsWithContent() as $field)
		{
			$columns[] = array('handle' => $field->handle, 'column' => 'field_'.$field->handle);
		}

		$contentService->fieldContext = $originalFieldContext;

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
