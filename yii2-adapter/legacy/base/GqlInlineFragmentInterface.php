<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * GqlInlineFragmentInterface defines the common interface to be implemented by GraphQL inline fragments contained by fields.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
interface GqlInlineFragmentInterface
{
    /**
     * Get the field context for all subfields in this fragment.
     *
     * @return string
     */
    public function getFieldContext(): string;

    /**
     * Get the eager loading prefix for all subfields in this fragment.
     *
     * @return string
     */
    public function getEagerLoadingPrefix(): string;
}
