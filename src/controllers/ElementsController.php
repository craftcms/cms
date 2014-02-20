<?php
namespace Craft;

/**
 * Element actions
 */
class ElementsController extends BaseController
{
	/**
	 * Renders and returns the body of an ElementSelectorModal.
	 */
	public function actionGetModalBody()
	{
		$this->requireAjaxRequest();

		$showSources = craft()->request->getParam('sources');
		$context = craft()->request->getParam('context');
		$elementType = $this->_getElementType();

		$sources = $elementType->getSources($context);

		if (is_array($showSources))
		{
			foreach (array_keys($sources) as $source)
			{
				if (!in_array($source, $showSources))
				{
					unset($sources[$source]);
				}
			}
		}
		else
		{
			// Maybe we were asked for a specific point in a source?
			$singleSource = $elementType->resolveSingleSource($showSources);
			if ($singleSource)
			{
				$sources = $singleSource;
			}
		}

		$this->renderTemplate('_elements/modalbody', array(
			'elementType' => $elementType,
			'sources'     => $sources
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
			$sources = $elementType->getSources($context);
			$source = $this->_findSource($sources, $sourceKey);

			// If this failed, maybe this is a specific point in a source?
			if (!$source)
			{
				$source = $elementType->resolveSingleSource($sourceKey);
				if (is_array($source))
				{
					$source = reset($source);
				}
			}

			if (!$source)
			{
				return false;
			}

			if (!empty($source['criteria']))
			{
				$criteria->setAttributes($source['criteria']);
			}
		}

		if ($search = craft()->request->getParam('search'))
		{
			$criteria->search = $search;
		}

		if ($offset = craft()->request->getParam('offset'))
		{
			$criteria->offset = $offset;
		}

		$variables = array(
			'viewMode'            => $viewState['mode'],
			'context'             => $context,
			'elementType'         => new ElementTypeVariable($elementType),
			'source'              => (isset($source) ? $source : null),
			'disabledElementIds'  => $disabledElementIds,
		);

		switch ($viewState['mode'])
		{
			case 'table':
			{
				// Make sure the attribute is actually allowed
				$variables['attributes'] = $elementType->defineTableAttributes($sourceKey);

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
				$variables['structure'] = craft()->structures->getStructureById($source['structureId']);

				$criteria->limit = null;
				$criteria->offset = null;
			}
		}

		// Find the elements!
		$variables['elements'] = $criteria->find();

		if (!$criteria->offset)
		{
			$template = 'container';
		}
		else
		{
			$template = 'elements';
		}

		$html = craft()->templates->render('_elements/'.$viewState['mode'].'view/'.$template, $variables);

		if ($viewState['mode'] != 'structure')
		{
			$totalVisible = $criteria->offset + $criteria->limit;
			$remainingElements = $criteria->total() - $totalVisible;
		}
		else
		{
			$totalVisible = null;
			$remainingElements = 0;
		}

		$this->returnJson(array(
			'html'         => $html,
			'headHtml'     => craft()->templates->getHeadHtml(),
			'footHtml'     => craft()->templates->getFootHtml(),
			'totalVisible' => $totalVisible,
			'more'         => ($remainingElements > 0),
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
	 * Returns the criteria for a given source.
	 *
	 * @param array  $sources
	 * @param string $sourceKey
	 * @return array|null
	 */
	private function _findSource($sources, $sourceKey)
	{
		if (isset($sources[$sourceKey]))
		{
			return $sources[$sourceKey];
		}
		else
		{
			// Look through any nested sources
			foreach ($sources as $key => $source)
			{
				if (!empty($source['nested']) && ($nestedSource = $this->_findSource($source['nested'], $sourceKey)))
				{
					return $nestedSource;
				}
			}
		}

		return null;
	}

	/**
	 * Returns the editor HTML for a given element.
	 *
	 * @access private
	 * @param BaseElementModel $element
	 * @param bool             $includeLocales
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
