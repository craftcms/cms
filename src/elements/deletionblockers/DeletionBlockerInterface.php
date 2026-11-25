<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

/**
 * DeletionBlockerInterface defines the common interface to be implemented by element deletion blocker classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
interface DeletionBlockerInterface
{
    /**
     * Returns whether the blocker should be shown.
     */
    public function isActive(): bool;

    /**
     * Returns a text summary of the blocker.
     */
    public function getSummary(): string;

    /**
     * Returns the blocker details HTML, to be shown when the blocker view is expanded.
     */
    public function getDetails(): ?string;

    /**
     * Returns an array of action buttons that can be taken to resolve the blocker.
     *
     * Each action button is defined by an array with the following keys:
     *
     * - `id` _(optional)_ – The button’s ID
     * - `class` _(optional)_ – The button’s class
     * - `label` – The button’s label
     * - `icon` _(optional)_ – The button icon name
     * - `action` _(optional)_ – The controller action that the button should trigger; if omitted, the blocker will be treated as resolved when the button is pressed
     * - `params` _(optional)_ – Additional request parameters that should be sent to the controller action (an `elementIds` param will be sent automatically)
     * - `callback` _(optional)_ – JavaScript code that should be executed when the button is activated
     * - `destructive` – Whether the action is destructive
     * - `confirm` _(optional)_ – A confirmation message that should be presented to the user before triggering the action
     * - `requireElevatedSession` _(optional)_ – Whether an elevated session is required before the action is triggered
     * - `attributes` _(optional)_ – Any HTML attributes that should be set on the `<button>` tag
     *
     * If `action` is defined, the corresponding controller action can return a JSON object with the following key:
     *
     * - `message` – A message that should be shown below the blocker to indicate that it is resolved.
     *
     * If `callback` is defined, it should be set to JavaScript code that can expect the following predefined variables:
     *
     * - `elementType` – The element type being deleted
     * - `elementIds` – An array of the element IDs being deleted
     * - `siteId` – The site ID the elements were loaded in, if applicable
     * - `ownerId` – The owner element ID the elements were loaded with, if applicable
     * - `withDescendants` – Whether the elements are being deleted with their descendants
     * - `hardDelete` – Whether the elements are being hard-deleted
     * - `resolve` – A function that should be called once the action is resolved
     * - `reject` – A function that should be called if the action is aborted
     * - `blocker` – The `Craft.ElementDeletionManager.Blocker` instance
     * - `action` – The current action config
     *
     * A custom success message can be passed to `resolve()` like so:
     *
     * ```js
     * resolve('This is now resolved!');
     * ```
     */
    public function getActions(): array;
}
