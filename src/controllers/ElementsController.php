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
		$source = craft()->request->getParam('selectedSource');
		$disabledElementIds = craft()->request->getParam('disabledElementIds');
		$view = craft()->request->getParam('view', 'table');

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
			if (!$source || !isset($sourcs[$source]))
			{
				$source = array_shift(array_keys($sources));
			}
		}
		else
		{
			$source = null;
		}

		$elementsHtml = $this->_renderElementsHtml($elementType, $source, null, 0, $view, $disabledElementIds, true);

		$this->renderTemplate('_elements/selectormodal', array(
			'sources'        => $sources,
			'selectedSource' => $source,
			'elementsHtml'   => $elementsHtml
		));
	}

	/**
	 * Renders and returns the list of elements in an ElementSelectorModal.
	 */
	public function actionGetElements()
	{
		$elementType = $this->_getElementType();
		$source = craft()->request->getParam('source');
		$search = craft()->request->getParam('search');
		$offset = craft()->request->getParam('offset', 0);
		$view = craft()->request->getParam('view', 'table');

		$this->_renderElementsHtml($elementType, $source, $search, $offset, $view);
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
	 * Renders the updated list of elements for an ElementSelectorModal.
	 *
	 * @access private
	 * @param BaseElementType $elementType
	 * @param string          $source
	 * @param string          $search
	 * @param int             $offset
	 * @param string          $view
	 * @param bool            $return
	 * @return string
	 */
	private function _renderElementsHtml(BaseElementType $elementType, $source, $search, $offset, $view, $disabledElementIds = array(), $return = false)
	{
		$criteria = craft()->elements->getCriteria($elementType->getClassHandle());

		if ($source)
		{
			$sources = $elementType->getSources();

			if (isset($sources[$source]))
			{
				$criteria->setAttributes($sources[$source]['criteria']);
			}
		}

		if ($search)
		{
			$criteria->search = $search;
		}

		$criteria->limit = 100;
		$criteria->offset = $offset;

		// Find the elements!
		$variables = array(
			'elements' => $criteria->find(),
			'disabledElementIds' => $disabledElementIds,
		);

		if ($view == 'table')
		{
			$variables['attributes'] = $elementType->defineTableAttributes($source);
			$template = '_elements/tableview';
		}
		else
		{
			$template = '_elements/thumbview';
		}

		return $this->renderTemplate($template, $variables, $return);
	}
}
