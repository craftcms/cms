<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\elements\db\ElementQueryInterface;

/**
 * Delete represents a Delete element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Delete extends ElementAction
{
	// Properties
	// =========================================================================

	/**
	 * @var string The confirmation message that should be shown before the elements get deleted
	 */
	public $confirmationMessage;

	/**
	 * @var string The message that should be shown after the elements get deleted
	 */
	public $successMessage;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTriggerLabel()
	{
		return Craft::t('app', 'Delete…');
	}

	/**
	 * @inheritdoc
	 */
	public static function isDestructive()
	{
		return true;
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getConfirmationMessage()
	{
		return $this->confirmationMessage;
	}

	/**
	 * @inheritdoc
	 */
	public function performAction(ElementQueryInterface $query)
	{
		Craft::$app->getElements()->deleteElementById($query->ids());
		$this->setMessage($this->successMessage);
		return true;
	}
}
