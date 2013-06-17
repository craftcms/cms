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

		$this->renderTemplate('_elements/modalbody', array(
			'sources' => $sources
		));
	}

	/**
	 * Renders and returns the list of elements in an ElementIndex.
	 */
	public function actionGetElements()
	{
		$mode = craft()->request->getParam('mode', 'index');
		$elementType = $this->_getElementType();
		$state = craft()->request->getParam('state', array());
		$disabledElementIds = craft()->request->getParam('disabledElementIds');

		$tableAttributes = $elementType->defineTableAttributes($state['source']);
		$criteria = $this->_getElementCriteria($elementType, $state, $tableAttributes);

		if (!$criteria->offset)
		{
			$elementContainerHtml = $this->renderTemplate('_elements/elementcontainer', array(
				'state'      => $state,
				'attributes' => $tableAttributes
			), true);
		}
		else
		{
			$elementContainerHtml = null;
		}

		$elementDataHtml = $this->renderTemplate('_elements/elementdata', array(
			'mode'               => $mode,
			'elementType'        => new ElementTypeVariable($elementType),
			'attributes'         => $tableAttributes,
			'elements'           => $criteria->find(),
			'disabledElementIds' => $disabledElementIds,
		), true);

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
	 * Returns the full path to the selected source.
	 *
	 * @param array  $sources
	 * @param string $selectedSource
	 * @return array|null
	 */
	private function _getSourcePath($sources, $selectedSource)
	{
		if (isset($sources[$selectedSource]))
		{
			return array($selectedSource);
		}
		else
		{
			// Look through any nested sources
			foreach ($sources as $key => $source)
			{
				if (!empty($source['nested']) && ($nestedSourcePath = $this->_getSourcePath($source['nested'], $selectedSource)))
				{
					return array_merge(array($key), $nestedSourcePath);
				}
			}
		}

		return null;
	}

	/**
	 * Returns the criteria for a given source.
	 *
	 * @param array  $sources
	 * @param string $selectedSource
	 * @return array|null
	 */
	private function _getSourceCriteria($sources, $selectedSource)
	{
		if (isset($sources[$selectedSource]))
		{
			if (isset($sources[$selectedSource]['criteria']))
			{
				return $sources[$selectedSource]['criteria'];
			}
			else
			{
				return array();
			}
		}
		else
		{
			// Look through any nested sources
			foreach ($sources as $key => $source)
			{
				if (!empty($source['nested']) && ($nestedSourceCriteria = $this->_getSourceCriteria($source['nested'], $selectedSource)))
				{
					return $nestedSourceCriteria;
				}
			}
		}

		return null;
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
	 * @param array           $tableAttributes
	 * @return ElementCriteriaModel
	 */
	private function _getElementCriteria($elementType, $state, $tableAttributes)
	{
		$baseCriteria = craft()->request->getPost('criteria');
		$criteria = craft()->elements->getCriteria($elementType->getClassHandle(), $baseCriteria);

		if (!empty($state['source']))
		{
			$sources = $elementType->getSources();
			$sourceCriteria = $this->_getSourceCriteria($sources, $state['source']);

			if ($sourceCriteria)
			{
				$criteria->setAttributes($sourceCriteria);
			}
		}

		if (!empty($state['order']))
		{
			// Validate it
			foreach ($tableAttributes as $attribute)
			{
				if ($attribute['attribute'] == $state['order'])
				{
					$criteria->order = $state['order'].' '.$state['sort'];
					break;
				}
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

		return $criteria;
	}
}
