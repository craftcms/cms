<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\enums\ElementType;
use craft\app\errors\HttpException;
use craft\app\helpers\ElementHelper;
use craft\app\helpers\StringHelper;
use craft\app\models\BaseElementModel;

/**
 * The ElementsController class is a controller that handles various element related actions including retrieving and
 * saving element and their corresponding HTML.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
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
	 * @return null
	 */
	public function actionGetModalBody()
	{
		$sourceKeys = Craft::$app->request->getParam('sources');
		$elementType = $this->getElementType();
		$context = $this->getContext();

		if (is_array($sourceKeys))
		{
			$sources = [];

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

		$this->renderTemplate('_elements/modalbody', [
			'context'     => $context,
			'elementType' => $elementType,
			'sources'     => $sources,
			'showSidebar' => (count($sources) > 1 || ($sources && !empty($sources[array_shift(array_keys($sources))]['nested'])))
		]);
	}

	/**
	 * Returns the HTML for an element editor HUD.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionGetEditorHtml()
	{
		$elementId = Craft::$app->request->getRequiredPost('elementId');
		$localeId = Craft::$app->request->getPost('locale');
		$elementTypeClass = Craft::$app->elements->getElementTypeById($elementId);
		$element = Craft::$app->elements->getElementById($elementId, $elementTypeClass, $localeId);

		if (!$element || !$element->isEditable())
		{
			throw new HttpException(403);
		}

		$includeLocales = (bool) Craft::$app->request->getPost('includeLocales', false);

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
		$elementId = Craft::$app->request->getRequiredPost('elementId');
		$localeId = Craft::$app->request->getRequiredPost('locale');
		$elementTypeClass = Craft::$app->elements->getElementTypeById($elementId);
		$element = Craft::$app->elements->getElementById($elementId, $elementTypeClass, $localeId);

		if (!ElementHelper::isElementEditable($element) || !$element)
		{
			throw new HttpException(403);
		}

		$namespace = Craft::$app->request->getRequiredPost('namespace');
		$params = Craft::$app->request->getPost($namespace);

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
		$elementType = Craft::$app->elements->getElementType($element->elementType);

		if ($elementType->saveElement($element, $params))
		{
			$this->returnJson([
				'success'   => true,
				'newTitle'  => (string) $element,
				'cpEditUrl' => $element->getCpEditUrl(),
			]);
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
		$categoryIds = Craft::$app->request->getParam('categoryIds', []);

		// Fill in the gaps
		$categoryIds = Craft::$app->categories->fillGapsInCategoryIds($categoryIds);

		if ($categoryIds)
		{
			$criteria = Craft::$app->elements->getCriteria(ElementType::Category);
			$criteria->id = $categoryIds;
			$criteria->locale = Craft::$app->request->getParam('locale');
			$criteria->status = null;
			$criteria->localeEnabled = null;
			$criteria->limit = Craft::$app->request->getParam('limit');
			$categories = $criteria->find();
		}
		else
		{
			$categories = [];
		}

		$html = Craft::$app->templates->render('_components/fieldtypes/Categories/input', [
			'elements' => $categories,
			'id'       => Craft::$app->request->getParam('id'),
			'name'     => Craft::$app->request->getParam('name'),
		]);

		$this->returnJson([
			'html' => $html,
		]);
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
				$response['locales'] = [];

				foreach ($localeIds as $localeId)
				{
					$locale = Craft::$app->i18n->getLocaleById($localeId);

					$response['locales'][] = [
						'id'   => $localeId,
						'name' => $locale->getName()
					];
				}
			}
			else
			{
				$response['locales'] = null;
			}
		}

		$response['locale'] = $element->locale;

		$elementType = Craft::$app->elements->getElementType($element->elementType);

		$namespace = 'editor_'.StringHelper::randomString(10);
		Craft::$app->templates->setNamespace($namespace);

		$response['html'] = '<input type="hidden" name="namespace" value="'.$namespace.'">' .
			'<input type="hidden" name="elementId" value="'.$element->id.'">' .
			'<input type="hidden" name="locale" value="'.$element->locale.'">' .
			'<div>' .
			Craft::$app->templates->namespaceInputs($elementType->getEditorHtml($element)) .
			'</div>';

		$response['headHtml'] = Craft::$app->templates->getHeadHtml();
		$response['footHtml'] = Craft::$app->templates->getFootHtml();

		$this->returnJson($response);
	}
}
