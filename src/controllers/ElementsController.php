<?php
namespace Craft;

/**
 * Element actions.
 *
 * @package craft.app.controllers
 */
class ElementsController extends BaseController
{
	/**
	 * Renders and returns the body of an ElementSelectorModal.
	 */
	public function actionGetModalBody()
	{
		$this->requireAjaxRequest();

		$sourceKeys = craft()->request->getParam('sources');
		$context = craft()->request->getParam('context');
		$elementType = $this->_getElementType();

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
	 * Renders and returns the list of elements in an ElementIndex.
	 */
	public function actionGetElements()
	{
		$context = craft()->request->getParam('context');
		$elementType = $this->_getElementType();
		$sourceKey = craft()->request->getParam('source');
		$viewState = craft()->request->getParam('viewState');
		$disabledElementIds = craft()->request->getParam('disabledElementIds', array());

		if (empty($viewState['mode']))
		{
			$viewState['mode'] = 'table';
		}

		$baseCriteria = craft()->request->getPost('criteria');
		$criteria = craft()->elements->getCriteria($elementType->getClassHandle(), $baseCriteria);
		$criteria->limit = 50;

		if ($sourceKey)
		{
			$source = $elementType->getSource($sourceKey, $context);

			if (!$source)
			{
				return false;
			}

			if (!empty($source['criteria']))
			{
				$criteria->setAttributes($source['criteria']);
			}
		}
		else
		{
			$source = null;
		}

		if ($search = craft()->request->getParam('search'))
		{
			$criteria->search = $search;
		}

		if ($offset = craft()->request->getParam('offset'))
		{
			$criteria->offset = $offset;
		}

		$html = $elementType->getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context);

		$totalElementsInBatch = count($criteria);
		$totalVisible = $criteria->offset + $totalElementsInBatch;

		if ($criteria->limit)
		{
			// We'll risk a pointless additional Ajax request in the unlikely event that there are exactly a factor of 50 elements, rather than running two separate element queries
			$more = ($totalElementsInBatch == $criteria->limit);
		}
		else
		{
			$more = false;
		}

		$this->returnJson(array(
			'html'         => $html,
			'headHtml'     => craft()->templates->getHeadHtml(),
			'footHtml'     => craft()->templates->getFootHtml(),
			'totalVisible' => $totalVisible,
			'more'         => $more,
		));
	}

	/**
	 * Returns the HTML for an element editor HUD.
	 */
	public function actionGetEditorHtml()
	{
		$this->requireAjaxRequest();

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
	 */
	public function actionSaveElement()
	{
		$this->requireAjaxRequest();

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
				'success'  => true,
				'newTitle' => (string) $element
			));
		}
		else
		{
			$this->_returnEditorHtml($element, false);
		}
	}

	/**
	 * Returns the element type based on the posted element type class.
	 *
	 * @access private
	 * @return BaseElementType
	 * @throws Exception
	 */
	private function _getElementType()
	{
		$class = craft()->request->getRequiredParam('elementType');
		$elementType = craft()->elements->getElementType($class);

		if (!$elementType)
		{
			throw new Exception(Craft::t('No element type exists with the class “{class}”', array('class' => $class)));
		}

		return $elementType;
	}

	/**
	 * Returns the editor HTML for a given element.
	 *
	 * @access private
	 * @param BaseElementModel $element
	 * @param bool $includeLocales
	 * @throws HttpException
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
			'</div>' .
			craft()->templates->getHeadHtml() .
			craft()->templates->getFootHtml();

		$this->returnJson($response);
	}
}
