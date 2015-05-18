<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\elements\Category;
use craft\app\errors\HttpException;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\ElementHelper;
use craft\app\helpers\StringHelper;

/**
 * The ElementsController class is a controller that handles various element related actions including retrieving and
 * saving element and their corresponding HTML.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementsController extends BaseElementsController
{
	// Public Methods
	// =========================================================================

	/**
	 * Renders and returns the body of an ElementSelectorModal.
	 *
	 * @return string The rendering result
	 */
	public function actionGetModalBody()
	{
		$sourceKeys = Craft::$app->getRequest()->getParam('sources');
		$elementType = $this->getElementType();
		$context = $this->getContext();

		if (is_array($sourceKeys))
		{
			$sources = [];

			foreach ($sourceKeys as $key)
			{
				$source = $elementType::getSourceByKey($key, $context);

				if ($source)
				{
					$sources[$key] = $source;
				}
			}
		}
		else
		{
			$sources = $elementType::getSources($context);
		}

		if (!empty($sources) && count($sources) === 1)
		{
			$firstSource = ArrayHelper::getFirstValue($sources);
			$showSidebar = !empty($firstSource['nested']);
		}
		else
		{
			$showSidebar = !empty($sources);
		}

		return $this->renderTemplate('_elements/modalbody', [
			'context'      => $context,
			'elementType'  => $elementType,
			'sources'      => $sources,
			'showSidebar'  => $showSidebar,
		]);
	}

	/**
	 * Returns the HTML for an element editor HUD.
	 *
	 * @return Response
	 * @throws HttpException
	 */
	public function actionGetEditorHtml()
	{
		$elementId = Craft::$app->getRequest()->getRequiredBodyParam('elementId');
		$localeId = Craft::$app->getRequest()->getBodyParam('locale');
		$elementType = Craft::$app->getElements()->getElementTypeById($elementId);
		$element = Craft::$app->getElements()->getElementById($elementId, $elementType, $localeId);

		if (!$element || !$element->isEditable())
		{
			throw new HttpException(403);
		}

		$includeLocales = (bool) Craft::$app->getRequest()->getBodyParam('includeLocales', false);

		return $this->_getEditorHtmlResponse($element, $includeLocales);
	}

	/**
	 * Saves an element.
	 *
	 * @return Response
	 * @throws HttpException
	 */
	public function actionSaveElement()
	{
		$elementId = Craft::$app->getRequest()->getRequiredBodyParam('elementId');
		$localeId = Craft::$app->getRequest()->getRequiredBodyParam('locale');
		$elementType = Craft::$app->getElements()->getElementTypeById($elementId);
		$element = Craft::$app->getElements()->getElementById($elementId, $elementType, $localeId);

		if (!ElementHelper::isElementEditable($element) || !$element)
		{
			throw new HttpException(403);
		}

		$namespace = Craft::$app->getRequest()->getRequiredBodyParam('namespace');
		$params = Craft::$app->getRequest()->getBodyParam($namespace);

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
		if ($element::saveElement($element, $params))
		{
			return $this->asJson([
				'success'   => true,
				'newTitle'  => (string) $element,
				'cpEditUrl' => $element->getCpEditUrl(),
			]);
		}
		else
		{
			return $this->_getEditorHtmlResponse($element, false);
		}
	}

	/**
	 * Returns the HTML for a Categories field input, based on a given list of selected category IDs.
	 *
	 * @return null
	 */
	public function actionGetCategoriesInputHtml()
	{
		$categoryIds = Craft::$app->getRequest()->getParam('categoryIds', []);

		// Fill in the gaps
		$categoryIds = Craft::$app->getCategories()->fillGapsInCategoryIds($categoryIds);

		if ($categoryIds)
		{
			$categories = Category::find()
				->id($categoryIds)
				->locale(Craft::$app->getRequest()->getParam('locale'))
				->status(null)
				->localeEnabled(false)
				->limit(Craft::$app->getRequest()->getParam('limit'))
				->all();
		}
		else
		{
			$categories = [];
		}

		$html = Craft::$app->getView()->renderTemplate('_components/fieldtypes/Categories/input', [
			'elements' => $categories,
			'id'       => Craft::$app->getRequest()->getParam('id'),
			'name'     => Craft::$app->getRequest()->getParam('name'),
		]);

		return $this->asJson([
			'html' => $html,
		]);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the editor HTML response for a given element.
	 *
	 * @param ElementInterface $element
	 * @param bool             $includeLocales
	 * @return Response
	 * @throws HttpException
	 */
	private function _getEditorHtmlResponse(ElementInterface $element, $includeLocales)
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
				$response['locales'] = [];

				foreach ($localeIds as $localeId)
				{
					$locale = Craft::$app->getI18n()->getLocaleById($localeId);

					$response['locales'][] = [
						'id'   => $localeId,
						'name' => $locale->getDisplayName(Craft::$app->language)
					];
				}
			}
			else
			{
				$response['locales'] = null;
			}
		}

		$response['locale'] = $element->locale;

		$namespace = 'editor_'.StringHelper::randomString(10);
		Craft::$app->getView()->setNamespace($namespace);

		$response['html'] = '<input type="hidden" name="namespace" value="'.$namespace.'">' .
			'<input type="hidden" name="elementId" value="'.$element->id.'">' .
			'<input type="hidden" name="locale" value="'.$element->locale.'">' .
			'<div>' .
			Craft::$app->getView()->namespaceInputs($element::getEditorHtml($element)) .
			'</div>';

		$response['headHtml'] = Craft::$app->getView()->getHeadHtml();
		$response['footHtml'] = Craft::$app->getView()->getBodyEndHtml(true);

		return $this->asJson($response);
	}
}
