<?php
namespace Craft;

/**
 * The ElementIndexController class is a controller that handles various element index related actions.
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
class ElementIndexController extends BaseElementsController
{
	// Properties
	// =========================================================================

	/**
	 * @var IElementType
	 */
	private $_elementType;

	/**
	 * @var string
	 */
	private $_context;

	/**
	 * @var string
	 */
	private $_sourceKey;

	/**
	 * @var array
	 */
	private $_viewState;

	/**
	 * @var array|null
	 */
	private $_source;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		parent::init();

		$this->_elementType = $this->getElementType();
		$this->_context     = $this->getContext();
		$this->_sourceKey   = craft()->request->getParam('source');
		$this->_viewState   = craft()->request->getRequiredParam('viewState');
		$this->_source      = $this->_getSource();
	}

	/**
	 * Renders and returns the list of elements in an ElementIndex.
	 *
	 * @return bool
	 */
	public function actionGetElements()
	{
		$disabledElementIds = craft()->request->getParam('disabledElementIds', array());

		if (empty($this->_viewState['mode']))
		{
			$this->_viewState['mode'] = 'table';
		}

		$baseCriteria = craft()->request->getPost('criteria');
		$criteria = craft()->elements->getCriteria($this->_elementType->getClassHandle(), $baseCriteria);
		$criteria->limit = 50;

		if ($search = craft()->request->getParam('search'))
		{
			$criteria->search = $search;
		}

		$actions = array();

		// Is a specific source selected?
		if ($this->_source)
		{
			// Does the source specify any criteria attributes?
			if (!empty($this->_source['criteria']))
			{
				$criteria->setAttributes($this->_source['criteria']);
			}
		}

		if ($offset = craft()->request->getParam('offset'))
		{
			$criteria->offset = $offset;
		}

		$html = $this->_elementType->getIndexHtml($criteria, $disabledElementIds, $this->_viewState, $this->_sourceKey, $this->_context);

		$totalElementsInBatch = count($criteria);
		$totalVisible = $criteria->offset + $totalElementsInBatch;

		if ($criteria->limit)
		{
			// We'll risk a pointless additional Ajax request in the unlikely event that there are exactly a factor of
			// 50 elements, rather than running two separate element queries
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
			'actions'      => $actions,
		));
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the selected source info.
	 *
	 * @throws HttpException
	 * @return array|null
	 */
	private function _getSource()
	{
		if ($this->_sourceKey)
		{
			$source = $this->_elementType->getSource($this->_sourceKey, $this->_context);

			if (!$source)
			{
				// That wasn't a valid source, or the user doesn't have access to it in this context
				throw new HttpException(404);
			}

			return $source;
		}
	}
}
