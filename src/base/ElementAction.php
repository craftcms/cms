<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use craft\app\elements\db\ElementQueryInterface;

/**
 * ElementAction is the base class for classes representing element actions in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class ElementAction extends SavableComponent implements ElementActionInterface
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function isDestructive()
	{
		return false;
	}

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_message;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTriggerLabel()
	{
		return static::displayName();
	}

	/**
	 * @inheritdoc
	 */
	public function getTriggerHtml()
	{
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function getConfirmationMessage()
	{
	}

	/**
	 * @inheritdoc
	 */
	public function performAction(ElementQueryInterface $criteria)
	{
		return true;
	}

	/**
	 * @inheritdoc
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
	 * Sets the message that should be displayed to the user after the action is performed.
	 *
	 * @param array $message The message that should be displayed to the user after the action is performed.
	 *
	 * @return null
	 */
	protected function setMessage($message)
	{
		$this->_message = $message;
	}
}
