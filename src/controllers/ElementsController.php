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
		$elementsHtml = $this->_renderElementsHtml($elementType, $state, $criteria, $disabledElementIds, true);

		$this->renderTemplate('_elements/selectormodal', array(
			'sources'        => $sources,
			'selectedSource' => $state['source'],
			'elementsHtml'   => $elementsHtml
		));
	}

	/**
	 * Renders and returns the list of elements in an ElementSelectorModal.
	 */
	public function actionGetElements()
	{
		$elementType = $this->_getElementType();
		$state = craft()->request->getParam('state', array());
		$disabledElementIds = craft()->request->getParam('disabledElementIds');

		$criteria = $this->_getElementCriteria($elementType, $state);
		$this->_renderElementsHtml($elementType, $state, $criteria, $disabledElementIds);
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
	 * Renders the updated list of elements for an ElementSelectorModal.
	 *
	 * @access private
	 * @param BaseElementType      $elementType
	 * @param array                $state
	 * @param ElementCriteriaModel $criteria
	 * @param array                $disabledElementIds
	 * @param bool                 $return
	 * @return string
	 */
	private function _renderElementsHtml(BaseElementType $elementType, $state, $criteria, $disabledElementIds = array(), $return = false)
	{
		$variables = array(
			'elements'           => $criteria->find(),
			'disabledElementIds' => $disabledElementIds,
			'state'              => $state,
		);

		if (!isset($state['view']))
		{
			$state['view'] = 'table';
		}

		if ($state['view'] == 'table')
		{
			$variables['attributes'] = $elementType->defineTableAttributes($state['source']);
			$template = '_elements/tableview';
		}
		else
		{
			$template = '_elements/thumbview';
		}

		return $this->renderTemplate($template, $variables, $return);
	}
}
