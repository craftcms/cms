<?php
namespace craft\app\elementactions;

use craft\app\components\BaseComponentType;
use craft\app\models\BaseModel;
use craft\app\models\ElementCriteria        as ElementCriteriaModel;
use craft\app\models\Params                 as ParamsModel;

/**
 * Element Action base class
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.elementactions
 * @since     3.0
 */
abstract class BaseElementAction extends BaseComponentType implements ElementActionInterface
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
	 * @inheritDoc ElementActionInterface::isDestructive()
	 *
	 * @return bool
	 */
	public function isDestructive()
	{
		return false;
	}

	/**
	 * @inheritDoc ElementActionInterface::getParams()
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
	 * @inheritDoc ElementActionInterface::setParams()
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
	 * @inheritDoc ElementActionInterface::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		return null;
	}

	/**
	 * @inheritDoc ElementActionInterface::getConfirmationMessage()
	 *
	 * @return string|null
	 */
	public function getConfirmationMessage()
	{
	}

	/**
	 * @inheritDoc ElementActionInterface::performAction()
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
	 * @inheritDoc ElementActionInterface::getMessage()
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
		return new ParamsModel($this->defineParams());
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
