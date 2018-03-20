<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\db\ElementQueryInterface;

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
     * @return bool Whether this action is destructive in nature.
     */
    public static function isDestructive(): bool;

    // Public Methods
    // =========================================================================

    /**
     * Returns the action’s trigger label.
     *
     * @return string The action’s trigger label
     */
    public function getTriggerLabel(): string;

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
     * @return bool Whether the action was performed successfully.
     */
    public function performAction(ElementQueryInterface $query): bool;

    /**
     * Returns the message that should be displayed to the user after the action is performed.
     *
     * @return string|null The message that should be displayed to the user.
     */
    public function getMessage();
}
