<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use craft\app\components\BaseComponentType;
use craft\app\base\Model;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\models\Params as ParamsModel;

/**
 * Element Action base class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	 * @return Model
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
	 * @param array|Model $values
	 *
	 * @return null
	 */
	public function setParams($values)
	{
		if ($values)
		{
			if ($values instanceof Model)
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
	 * @return Model
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
		return [];
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
