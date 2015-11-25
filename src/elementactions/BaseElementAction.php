<?php
namespace Craft;

/**
 * Element Action base class
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @link      http://craftcms.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
abstract class BaseElementAction extends BaseComponentType implements IElementAction
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $componentType = 'ElementAction';

	/**
	 * @var
	 */
	private $_params;

	/**
	 * @var
	 */
	private $_message;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IElementAction::isDestructive()
	 *
	 * @return bool
	 */
	public function isDestructive()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementAction::getParams()
	 *
	 * @return BaseModel
	 */
	public function getParams()
	{
		if (!isset($this->_params))
		{
			$this->_params = $this->getParamsModel();
		}

		return $this->_params;
	}

	/**
	 * @inheritDoc IElementAction::setParams()
	 *
	 * @param array|BaseModel $values
	 *
	 * @return null
	 */
	public function setParams($values)
	{
		if ($values)
		{
			if ($values instanceof BaseModel)
			{
				$this->_params = $values;
			}
			else
			{
				$this->getParams()->setAttributes($values);
			}
		}
	}

	/**
	 * @inheritDoc IElementAction::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		return null;
	}

	/**
	 * @inheritDoc IElementAction::getConfirmationMessage()
	 *
	 * @return string|null
	 */
	public function getConfirmationMessage()
	{
	}

	/**
	 * @inheritDoc IElementAction::performAction()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		return true;
	}

	/**
	 * @inheritDoc IElementAction::getMessage()
	 *
	 * @return string|null
	 */
	public function getMessage()
	{
		if (isset($this->_message))
		{
			return $this->_message;
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the params model.
	 *
	 * @return BaseModel
	 */
	protected function getParamsModel()
	{
		return new Model($this->defineParams());
	}

	/**
	 * Defines the params.
	 *
	 * @return array
	 */
	protected function defineParams()
	{
		return array();
	}

	/**
	 * Sets the message that should be displayed to the user after the action is performed.
	 *
	 * @param array The message that should be displayed to the user after the action is performed.
	 *
	 * @return null
	 */
	protected function setMessage($message)
	{
		$this->_message = $message;
	}
}
