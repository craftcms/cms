<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsTwigComponent
{
    /**
     * @param string $name Name of the template
     * @param string|null $template Path to the template. ":" characters are replaced with "/"
     * @param string $attributesVar Variable to collect explicit HTML attributes
     */
    public function __construct(
        public string $name,
        public ?string $template = null,
        public string $attributesVar = 'attributes',
    ) {
    }
}
