<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * GqlInlineFragmentFieldInterface defines the common interface to be implemented by fields that support inline GraphQL fragments.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
interface GqlInlineFragmentFieldInterface
{
    /**
     * Returns a GraphQL fragment by its GraphQL fragment name.
     *
     * @param string $fragmentName
     * @return GqlInlineFragmentInterface
     */
    public function getGqlFragmentEntityByName(string $fragmentName): GqlInlineFragmentInterface;
}
