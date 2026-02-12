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
 * @phpstan-import-type EagerLoadingMap from ElementInterface
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
     * - `map` – an array defining source-target element mappings
     * - `elementType` *(optional)* – the fully qualified class name of the element type that should be eager-loaded,
     *   if each target element is of the same element type
     * - `criteria` *(optional)* – any criteria parameters that should be applied to the element query when fetching the
     *   eager-loaded elements
     * - `createElement` *(optional)* - an element factory function, which will be passed the element query, the current
     *   query result data, and the first source element that the result was eager-loaded for
     *
     * Each mapping listed in `map` should be an array with the following keys:
     * - `source` – the source element ID
     * - `target` – the target element ID
     * - `elementType` *(optional)* – the target element type (only checked for if the top-level array doesn’t specify
     *   an `elementType` key)
     *
     * Alternatively, the method can return an array of multiple sets of mappings, each with their own nested `map`,
     * `elementType`, `criteria`, and `createElement` keys.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @return EagerLoadingMap|EagerLoadingMap[]|null|false The eager-loading element ID mappings, false if no mappings exist, or null if the result
     * should be ignored.
     * @see ElementInterface::eagerLoadingMap()
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
