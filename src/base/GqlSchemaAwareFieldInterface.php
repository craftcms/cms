<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * GqlSchemaAwareFieldInterface defines the common interface to be implemented by custom fields that are aware of
 * GraphQL schema.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
interface GqlSchemaAwareFieldInterface
{
    /**
     * Returns true if this field exists for current GraphQL schema at all.
     *
     * @return bool
     */
    public function getExistsForCurrentGqlSchema(): bool;
}
