<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * GqlInlineFragmentFieldInterface defines the common interface to be implemented by fields that support inline GQL fragments.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
interface GqlInlineFragmentFieldInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Get a GQL fragment by its GQL fragment name.
     *
     * @param string $fragmentName
     * @return GqlInlineFragmentInterface
     */
    public function getGqlFragmentEntityByName(string $fragmentName): GqlInlineFragmentInterface;
}
