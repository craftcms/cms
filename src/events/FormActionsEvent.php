<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Form Actions Event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.10
 */
class FormActionsEvent extends Event
{
    /**
     * @var array The form actions that will be displayed for the current page.
     *
     * Each action should be defined by an array with any of the following keys:
     *
     * - `label` – The human-facing label for the action.
     * - `action` – The controller action path that should be requested when the action is selected.
     * - `redirect` – The `redirect` param that should be passed to the controller action if the action is selected. Note that this value should be
     *   hashed via `Craft::$app->security->hashData()`.
     * - `confirm` – A confirmation message that should be displayed when the action is selected.
     * - `params` – An array of param names/values that should be passed to the controller action if the action is selected.
     * - `destructive` – `true` or `false` depending on whether the action should be considered destructive.
     * - `shortcut` – `true` or `false` depending on whether the action should be triggered by the <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>S</kbd>
     *   keyboard shortcut.
     * - `shift` – `true` or `false` depending on whether the keyboard shortcut requires the <kbd>Shift</kbd> key to be pressed.
     */
    public array $formActions;
}
