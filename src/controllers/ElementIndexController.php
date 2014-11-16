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
 * @since     2.3
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
	 * @var array|null
	 */
	private $_source;

	/**
	 * @var array
	 */
	private $_viewState;

	/**
	 * @var ElementCriteriaModel
	 */
	private $_criteria;

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
		$this->_source      = $this->_getSource();
		$this->_viewState   = $this->_getViewState();
		$this->_criteria    = $this->_getCriteria();
	}

	/**
	 * Renders and returns an element index container, plus its first batch of elements.
	 *
	 * @return null
	 */
	public function actionGetElements()
	{
		$this->_respond(array(
			'html'    => $this->_getElementHtml(true),
		), true);
	}

	/**
	 * Renders and returns a subsequent batch of elements for an element index.
	 *
	 * @return null
	 */
	public function actionGetMoreElements()
	{
		$this->_respond(array(
			'html' => $this->_getElementHtml(false),
		), true);
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

	/**
	 * Returns the current view state.
	 *
	 * @return array
	 */
	private function _getViewState()
	{
		$viewState = craft()->request->getParam('viewState', array());

		if (empty($viewState['mode']))
		{
			$viewState['mode'] = 'table';
		}

		return $viewState;
	}

	/**
	 * Returns the element criteria based on the current params.
	 *
	 * @return ElementCriteriaModel
	 */
	private function _getCriteria()
	{
		$criteria = craft()->elements->getCriteria(
			$this->_elementType->getClassHandle(),
			craft()->request->getPost('criteria')
		);

		$criteria->limit = 50;
		$criteria->offset = craft()->request->getParam('offset');
		$criteria->search = craft()->request->getParam('search');

		// Does the source specify any criteria attributes?
		if (!empty($this->_source['criteria']))
		{
			$criteria->setAttributes($this->_source['criteria']);
		}

		return $criteria;
	}

	/**
	 * Returns the element HTML to be returned to the client.
	 *
	 * @param bool $includeContainer Whether the element container should be included in the HTML.
	 *
	 * @return string
	 */
	private function _getElementHtml($includeContainer)
	{
		$disabledElementIds = craft()->request->getParam('disabledElementIds', array());

		return $this->_elementType->getIndexHtml(
			$this->_criteria,
			$disabledElementIds,
			$this->_viewState,
			$this->_sourceKey,
			$this->_context,
			$includeContainer
		);
	}

	/**
	 * Responds to the request.
	 *
	 * @param array $responseData
	 * @param bool  $includeLoadMoreInfo
	 *
	 * @return null
	 */
	private function _respond($responseData, $includeLoadMoreInfo)
	{
		if ($includeLoadMoreInfo)
		{
			$totalElementsInBatch = count($this->_criteria);
			$responseData['totalVisible'] = $this->_criteria->offset + $totalElementsInBatch;

			if ($this->_criteria->limit)
			{
				// We'll risk a pointless additional Ajax request in the unlikely event that there are exactly a factor of
				// 50 elements, rather than running two separate element queries
				$responseData['more'] = ($totalElementsInBatch == $this->_criteria->limit);
			}
			else
			{
				$responseData['more'] = false;
			}
		}

		$responseData['headHtml'] = craft()->templates->getHeadHtml();
		$responseData['footHtml'] = craft()->templates->getFootHtml();

		$this->returnJson($responseData);
	}
}
