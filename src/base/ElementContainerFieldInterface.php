<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

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
}
