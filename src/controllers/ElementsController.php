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
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
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
			$sources = $elementType->getSources($context);
		}

		$this->renderTemplate('_elements/modalbody', array(
			'context'     => $context,
			'elementType' => $elementType,
			'sources'     => $sources,
			'showSidebar' => (count($sources) > 1 || ($sources && !empty($sources[array_shift(array_keys($sources))]['nested'])))
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
		$elementId = craft()->request->getRequiredPost('elementId');
		$localeId = craft()->request->getPost('locale');
		$elementTypeClass = craft()->elements->getElementTypeById($elementId);
		$element = craft()->elements->getElementById($elementId, $elementTypeClass, $localeId);

		if (!$element || !$element->isEditable())
		{
			throw new HttpException(403);
		}

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
		$elementId = craft()->request->getRequiredPost('elementId');
		$localeId = craft()->request->getRequiredPost('locale');
		$elementTypeClass = craft()->elements->getElementTypeById($elementId);
		$element = craft()->elements->getElementById($elementId, $elementTypeClass, $localeId);

		if (!$element || !ElementHelper::isElementEditable($element))
		{
			throw new HttpException(403);
		}

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
			'elements' => $categories,
			'id'       => craft()->request->getParam('id'),
			'name'     => craft()->request->getParam('name'),
		));

		$this->returnJson(array(
			'html' => $html,
		));
	}

	// Private Methods
	// =========================================================================

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

		$response['html'] = '<input type="hidden" name="namespace" value="'.$namespace.'">' .
			'<input type="hidden" name="elementId" value="'.$element->id.'">' .
			'<input type="hidden" name="locale" value="'.$element->locale.'">' .
			'<div>' .
			craft()->templates->namespaceInputs($elementType->getEditorHtml($element)) .
			'</div>';

		$response['headHtml'] = craft()->templates->getHeadHtml();
		$response['footHtml'] = craft()->templates->getFootHtml();

		$this->returnJson($response);
	}
}
