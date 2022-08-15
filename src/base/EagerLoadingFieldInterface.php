<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * EagerLoadingFieldInterface defines the common interface to be implemented by field classes that support eager-loading.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface EagerLoadingFieldInterface
{
    /**
     * Returns an array that maps source-to-target element IDs based on this custom field.
     *
     * This method aids in the eager-loading of elements when performing an element query. The returned array should
     * contain the following keys:
     * - `elementType` – the fully qualified class name of the element type that should be eager-loaded
     * - `map` – an array of element ID mappings, where each element is a sub-array with `source` and `target` keys.
     * - `criteria` *(optional)* – Any criteria parameters that should be applied to the element query when fetching the eager-loaded elements.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @return array|null|false The eager-loading element ID mappings, false if no mappings exist, or null if the result
     * should be ignored.
     */
    public function getEagerLoadingMap(array $sourceElements): array|null|false;

    /**
     * Returns an array that lists the scopes this custom field allows when eager-loading or null if eager-loading
     * should not be allowed in the GraphQL context.
     *
     * @return array|null
     * @since 3.3.0
     */
    public function getEagerLoadingGqlConditions(): ?array;
}
