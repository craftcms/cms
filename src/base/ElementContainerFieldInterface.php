<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\User;
use yii\base\Component as YiiComponent;

/**
 * ElementContainerFieldInterface defines the common interface to be implemented by field classes
 * that contain nested elements, which implement [[NestedElementInterface]].
 *
 * @mixin FieldTrait
 * @mixin YiiComponent
 * @mixin Model
 * @mixin SavableComponentTrait
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface ElementContainerFieldInterface extends FieldInterface
{
    /**
     * Returns the field layout providers that could be involved in nested elements.
     *
     * @return FieldLayoutProviderInterface[]
     */
    public function getFieldLayoutProviders(): array;

    /**
     * Returns the URI format for a nested element.
     *
     * @param NestedElementInterface $element
     * @return string|null
     */
    public function getUriFormatForElement(NestedElementInterface $element): ?string;

    /**
     * Returns the route that should be used when a nested element’s URI is requested.
     *
     * @param NestedElementInterface $element
     * @return mixed The route that the request should use, or null if no special action should be taken
     */
    public function getRouteForElement(NestedElementInterface $element): mixed;

    /**
     * Returns the sites a nested element is associated with.
     *
     * The function can either return an array of site IDs, or an array of sub-arrays,
     * each with the following keys:
     *
     * - `siteId` (integer) - The site ID
     * - `propagate` (boolean) – Whether the element should be propagated to this site on save (`true` by default)
     * - `enabledByDefault` (boolean) – Whether the element should be enabled in this site by default
     *   (`true` by default)
     *
     * @return array
     */
    public function getSupportedSitesForElement(NestedElementInterface $element): array;

    /**
     * Returns whether the given user is authorized to view an element’s edit page.
     *
     *  If they can view but not [[canSave()|save]], the edit form will either render statically,
     *  or be restricted to only saving changes as a draft, depending on [[canCreateDrafts()]].
     *
     * @param NestedElementInterface $element
     * @param User $user
     * @return bool|null
     */
    public function canViewElement(NestedElementInterface $element, User $user): ?bool;

    /**
     * Returns whether the given user is authorized to save an element in its current form.
     *
     * This will only be called if the element can be [[canView()|viewed]].
     *
     * @param NestedElementInterface $element
     * @param User $user
     * @return bool|null
     */
    public function canSaveElement(NestedElementInterface $element, User $user): ?bool;

    /**
     * Returns whether the given user is authorized to duplicate an element.
     *
     * This will only be called if the element can be [[canView()|viewed]] and/or [[canSave()|saved]].
     *
     * @param NestedElementInterface $element
     * @param User $user
     * @return bool|null
     */
    public function canDuplicateElement(NestedElementInterface $element, User $user): ?bool;

    /**
     * Returns whether the given user is authorized to delete an element.
     *
     * This will only be called if the element can be [[canView()|viewed]] and/or [[canSave()|saved]].
     *
     * @param NestedElementInterface $element
     * @param User $user
     * @return bool|null
     */
    public function canDeleteElement(NestedElementInterface $element, User $user): ?bool;

    /**
     * Returns whether the given user is authorized to delete an element for its current site.
     *
     * This will only be called if the element can be [[canView()|viewed]] and/or [[canSave()|saved]].
     *
     * @param NestedElementInterface $element
     * @param User $user
     * @return bool|null
     */
    public function canDeleteElementForSite(NestedElementInterface $element, User $user): ?bool;
}
