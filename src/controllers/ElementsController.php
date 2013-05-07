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
		$state = craft()->request->getParam('state', array());
		$disabledElementIds = craft()->request->getParam('disabledElementIds');

		$elementType = $this->_getElementType();
		$sources = $elementType->getSources();

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

		if ($sources)
		{
			if (empty($state['source']) || !isset($sources[$state['source']]))
			{
				$state['source'] = array_shift(array_keys($sources));
			}
		}
		else
		{
			$state['source'] = null;
		}

		$criteria = $this->_getElementCriteria($elementType, $state);

		$bodyHtml = $this->renderTemplate('_elements/modal/body', array(
			'sources'        => $sources,
			'selectedSource' => $state['source'],
		), true);

		$elementContainerHtml = $this->_renderModalElementContainerHtml($elementType, $state);
		$elementDataHtml = $this->_renderModalElementDataHtml($elementType, $state, $criteria, $disabledElementIds);

		$totalVisible = $criteria->offset + $criteria->limit;
		$remainingElements = $criteria->total() - $totalVisible;

		$this->returnJson(array(
			'bodyHtml'             => $bodyHtml,
			'elementContainerHtml' => $elementContainerHtml,
			'elementDataHtml'      => $elementDataHtml,
			'headHtml'             => craft()->templates->getHeadHtml(),
			'totalVisible'         => $totalVisible,
			'more'                 => ($remainingElements > 0),
		));
	}

	/**
	 * Renders and returns the list of elements in an ElementSelectorModal.
	 */
	public function actionGetModalElements()
	{
		$elementType = $this->_getElementType();
		$state = craft()->request->getParam('state', array());
		$disabledElementIds = craft()->request->getParam('disabledElementIds');

		$criteria = $this->_getElementCriteria($elementType, $state);

		if (!$criteria->offset)
		{
			$elementContainerHtml = $this->_renderModalElementContainerHtml($elementType, $state);
		}
		else
		{
			$elementContainerHtml = null;
		}

		$elementDataHtml = $this->_renderModalElementDataHtml($elementType, $state, $criteria, $disabledElementIds);

		$totalVisible = $criteria->offset + $criteria->limit;
		$remainingElements = $criteria->total() - $totalVisible;

		$this->returnJson(array(
			'elementContainerHtml' => $elementContainerHtml,
			'elementDataHtml'      => $elementDataHtml,
			'headHtml'             => craft()->templates->getHeadHtml(),
			'totalVisible'         => $totalVisible,
			'more'                 => ($remainingElements > 0),
		));
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
			throw new Exception(Craft::t('No element type exists with the class "{class}"', array('class' => $class)));
		}

		return $elementType;
	}

	/**
	 * Returns the element criteria based on the request parameters.
	 *
	 * @param BaseElementType $elementType
	 * @param array           $state
	 * @return ElementCriteriaModel
	 */
	private function _getElementCriteria($elementType, $state)
	{
		$criteria = craft()->elements->getCriteria($elementType->getClassHandle());

		if (!empty($state['source']))
		{
			$sources = $elementType->getSources();

			if (isset($sources[$state['source']]))
			{
				$criteria->setAttributes($sources[$state['source']]['criteria']);
			}
		}
		else
		{
			$state['source'] = null;
		}

		if (!empty($state['order']))
		{
			$criteria->order = $state['order'].' '.$state['sort'];
		}

		if ($search = craft()->request->getParam('search'))
		{
			$criteria->search = $search;
		}

		if ($offset = craft()->request->getParam('offset'))
		{
			$criteria->offset = $offset;
		}

		return $criteria;
	}

	/**
	 * Renders the element container HTML for the ElementSelectorModal.
	 *
	 * @access private
	 * @param BaseElementType      $elementType
	 * @param array                $state
	 * @return string
	 */
	private function _renderModalElementContainerHtml(BaseElementType $elementType, $state)
	{
		return $this->renderTemplate('_elements/modal/elementcontainer', array(
			'state'              => $state,
			'attributes'         => $elementType->defineTableAttributes($state['source'])
		), true);
	}

	/**
	 * Renders the element data HTML for the ElementSelectorModal.
	 *
	 * @access private
	 * @param BaseElementType      $elementType
	 * @param array                $state
	 * @param ElementCriteriaModel $criteria
	 * @param array                $disabledElementIds
	 * @return string
	 */
	private function _renderModalElementDataHtml(BaseElementType $elementType, $state, ElementCriteriaModel $criteria, $disabledElementIds)
	{
		return $this->renderTemplate('_elements/modal/elementdata', array(
			'attributes'         => $elementType->defineTableAttributes($state['source']),
			'elements'           => $criteria->find(),
			'disabledElementIds' => $disabledElementIds,
		), true);
	}
}
