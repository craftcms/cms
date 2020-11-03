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
 * @since 3.0.0
 */
interface ElementActionInterface extends ConfigurableComponentInterface
{
    /**
     * Returns whether this action is destructive in nature.
     *
     * @return bool Whether this action is destructive in nature.
     */
    public static function isDestructive(): bool;

    /**
     * Returns whether this is a download action.
     *
     * Download actions’ [[performAction()]] method should call one of these methods before returning `true`:
     *
     * - [[\yii\web\Response::sendFile()]]
     * - [[\yii\web\Response::sendContentAsFile()]]
     * - [[\yii\web\Response::sendStreamAsFile()]]
     *
     * @return bool Whether this is a download action
     * @since 3.5.0
     */
    public static function isDownload(): bool;

    /**
     * Sets the element type on the action.
     *
     * @param string $elementType
     */
    public function setElementType(string $elementType);

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
