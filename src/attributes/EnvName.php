<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\attributes;

use Attribute;

/**
 * Attribute EnvName
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
#[Attribute]
class EnvName
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
