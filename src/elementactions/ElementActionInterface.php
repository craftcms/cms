<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use craft\app\components\ComponentTypeInterface;
use craft\app\base\Model;
use craft\app\elements\db\ElementQueryInterface;

/**
 * Interface ElementActionInterface
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ElementActionInterface extends ComponentTypeInterface
{
	/**
	 * Returns whether this action is destructive in nature.
	 *
	 * @return bool Whether this action is destructive in nature.
	 */
	public function isDestructive();

	/**
	 * Returns the action’s params model.
	 *
	 * @return Model The action’s params model.
	 */
	public function getParams();

	/**
	 * Sets the param values.
	 *
	 * The values may come as a key/value array, or a [[Model]] object. Either way, this method should store the
	 * values on the model that is returned by [[getParams()]].
	 *
	 * @param array|Model $values The new param values.
	 *
	 * @return null
	 */
	public function setParams($values);

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
	 * @param ElementQueryInterface $query The element query defining which elements the action should affect.
	 *
	 * @return bool Whether the action was performed successfully.
	 */
	public function performAction(ElementQueryInterface $query);

	/**
	 * Returns the message that should be displayed to the user after the action is performed.
	 *
	 * @return string|null The message that should be displayed to the user.
	 */
	public function getMessage();
}
