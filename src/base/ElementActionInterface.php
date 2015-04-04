<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;

/**
 * ElementActionInterface defines the common interface to be implemented by element action classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ElementActionInterface extends SavableComponentInterface
{
	// Static
	// =========================================================================

	/**
	 * Returns whether this action is destructive in nature.
	 *
	 * @return boolean Whether this action is destructive in nature.
	 */
	public static function isDestructive();

	// Public Methods
	// =========================================================================

	/**
	 * Returns the action’s trigger label.
	 *
	 * @return string The action’s trigger label
	 */
	public function getTriggerLabel();

	/**
	 * Returns the action’s trigger HTML.
	 *
	 * @return string|null The action’s trigger HTML.
	 */
	public function getTriggerHtml();

	/**
	 * Returns a confirmation message that should be displayed before the action is performed.
	 *
	 * @return string|null The confirmation message, if any.
	 */
	public function getConfirmationMessage();

	/**
	 * Performs the action on any elements that match the given criteria.
	 *
	 * @param ElementQueryInterface|ElementQuery $query The element query defining which elements the action should affect.
	 * @return boolean Whether the action was performed successfully.
	 */
	public function performAction(ElementQueryInterface $query);

	/**
	 * Returns the message that should be displayed to the user after the action is performed.
	 *
	 * @return string|null The message that should be displayed to the user.
	 */
	public function getMessage();
}
