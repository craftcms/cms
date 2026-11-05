<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * InputSchemaFieldInterface defines the common interface to be implemented by field classes
 * that describe the values they accept with a JSON schema.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 * @mixin Field
 */
interface InputSchemaFieldInterface extends FieldInterface
{
    /**
     * Returns the JSON schema of the values the field accepts.
     *
     * @return array<string, mixed>
     */
    public function getInputSchema(): array;
}
