<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use craft\base\Serializable;
use craft\web\twig\AllowedInSandbox;

/**
 * Class IconData
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
#[AllowedInSandbox]
class IconData implements Serializable
{
    /**
     * Constructor
     *
     * @param string $name The icon name
     * @param string[] $styles The Font Awesome styles the icon is available in
     */
    public function __construct(
        public string $name,
        public array $styles,
    ) {
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return $this->name;
    }
}
