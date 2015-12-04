<?php
namespace Craft;

/**
 * The ElementsController class is a controller that handles various element related actions including retrieving and
 * saving element and their corresponding HTML.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class ElementsController extends BaseElementsController
{
	// Public Methods
	// =========================================================================

	/**
	 * Renders and returns the body of an ElementSelectorModal.
	 *
	 * @return null
	 */
	public function actionGetModalBody()
	{
		$sourceKeys = craft()->request->getParam('sources');
		$elementType = $this->getElementType();
		$context = $this->getContext();

		if (is_array($sourceKeys))
		{
			$sources = array();

			foreach ($sourceKeys as $key)
			{
				$source = $elementType->getSource($key, $context);

				if ($source)
				{
					$sources[$key] = $source;
				}
			}
		}
		else
		{
			$sources = craft()->elementIndexes->getSources($elementType->getClassHandle(), $context);
		}

		$source = ArrayHelper::getFirstValue($sources);

		$this->renderTemplate('_elements/modalbody', array(
			'context'     => $context,
			'elementType' => $elementType,
			'sources'     => $sources,
			'showSidebar' => (count($sources) > 1 || ($sources && !empty($source['nested'])))
		));
	}

	/**
	 * Returns the HTML for an element editor HUD.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionGetEditorHtml()
	{
		$element = $this->_getEditorElement();
		$includeLocales = (bool) craft()->request->getPost('includeLocales', false);

		return $this->_returnEditorHtml($element, $includeLocales);
	}

	/**
	 * Saves an element.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionSaveElement()
	{
		$element = $this->_getEditorElement();
		$namespace = craft()->request->getRequiredPost('namespace');
		$params = craft()->request->getPost($namespace);

		if (isset($params['title']))
		{
			$element->getContent()->title = $params['title'];
			unset($params['title']);
		}

		if (isset($params['fields']))
		{
			$fields = $params['fields'];
			$element->setContentFromPost($fields);
			unset($params['fields']);
		}

		// Either way, at least tell the element where its content comes from
		$element->setContentPostLocation($namespace.'.fields');

		// Now save it
		$elementType = craft()->elements->getElementType($element->elementType);

		if ($elementType->saveElement($element, $params))
		{
			$this->returnJson(array(
				'success'   => true,
				'id'        => $element->id,
				'locale'    => $element->locale,
				'newTitle'  => (string) $element,
				'cpEditUrl' => $element->getCpEditUrl(),
			));
		}
		else
		{
			$this->_returnEditorHtml($element, false);
		}
	}

	/**
	 * Returns the HTML for a Categories field input, based on a given list of selected category IDs.
	 *
	 * @return null
	 */
	public function actionGetCategoriesInputHtml()
	{
		$categoryIds = craft()->request->getParam('categoryIds', array());

		// Fill in the gaps
		$categoryIds = craft()->categories->fillGapsInCategoryIds($categoryIds);

		if ($categoryIds)
		{
			$criteria = craft()->elements->getCriteria(ElementType::Category);
			$criteria->id = $categoryIds;
			$criteria->locale = craft()->request->getParam('locale');
			$criteria->status = null;
			$criteria->localeEnabled = null;
			$criteria->limit = craft()->request->getParam('limit');
			$categories = $criteria->find();
		}
		else
		{
			$categories = array();
		}

		$html = craft()->templates->render('_components/fieldtypes/Categories/input', array(
			'elements'       => $categories,
			'id'             => craft()->request->getParam('id'),
			'name'           => craft()->request->getParam('name'),
			'selectionLabel' => craft()->request->getParam('selectionLabel'),
		));

		$this->returnJson(array(
			'html' => $html,
		));
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the element that is currently being edited.
	 *
	 * @throws HttpException
	 * @return BaseElementModel
	 */
	private function _getEditorElement()
	{
		$elementId = craft()->request->getPost('elementId');
		$localeId = craft()->request->getPost('locale');

		// Determine the element type
		$elementTypeClass = craft()->request->getPost('elementType');

		if ($elementTypeClass === null && $elementId !== null)
		{
			$elementTypeClass = craft()->elements->getElementTypeById($elementId);
		}

		if ($elementTypeClass === null)
		{
			throw new HttpException(400, Craft::t('POST param “{name}” doesn’t exist.', array('name' => 'elementType')));
		}

		// Make sure it's a valid element type
		$elementType = craft()->elements->getElementType($elementTypeClass);

		if (!$elementType)
		{
			throw new HttpException(404);
		}

		// Instantiate the element
		if ($elementId !== null)
		{
			$element = craft()->elements->getElementById($elementId, $elementTypeClass, $localeId);
		}
		else
		{
			$element = $elementType->populateElementModel(array());
		}

		if (!$element)
		{
			throw new HttpException(404);
		}

		// Populate it with any posted attributse
		$attributes = craft()->request->getPost('attributes', array());

		if ($localeId)
		{
			$attributes['locale'] = $localeId;
		}

		if ($attributes)
		{
			$element->setAttributes($attributes);
		}

		// Make sure it's editable
		if (!ElementHelper::isElementEditable($element))
		{
			throw new HttpException(403);
		}

		return $element;
	}

	/**
	 * Returns the editor HTML for a given element.
	 *
	 * @param BaseElementModel $element
	 * @param bool             $includeLocales
	 *
	 * @throws HttpException
	 * @return null
	 */
	private function _returnEditorHtml(BaseElementModel $element, $includeLocales)
	{
		$localeIds = ElementHelper::getEditableLocaleIdsForElement($element);

		if (!$localeIds)
		{
			throw new HttpException(403);
		}

		if ($includeLocales)
		{
			if (count($localeIds) > 1)
			{
				$response['locales'] = array();

				foreach ($localeIds as $localeId)
				{
					$locale = craft()->i18n->getLocaleById($localeId);

					$response['locales'][] = array(
						'id'   => $localeId,
						'name' => $locale->getName()
					);
				}
			}
			else
			{
				$response['locales'] = null;
			}
		}

		$response['locale'] = $element->locale;

		$elementType = craft()->elements->getElementType($element->elementType);

		$namespace = 'editor_'.StringHelper::randomString(10);
		craft()->templates->setNamespace($namespace);

		$response['html'] = '<input type="hidden" name="namespace" value="'.$namespace.'">';

		if ($element->id)
		{
			$response['html'] .= '<input type="hidden" name="elementId" value="'.$element->id.'">';
		}

		if ($element->locale)
		{
			$response['html'] .= '<input type="hidden" name="locale" value="'.$element->locale.'">';
		}

		$response['html'] .= '<div class="meta">' .
			craft()->templates->namespaceInputs($elementType->getEditorHtml($element)) .
			'</div>';

		$response['headHtml'] = craft()->templates->getHeadHtml();
		$response['footHtml'] = craft()->templates->getFootHtml();

		$this->returnJson($response);
	}
}
