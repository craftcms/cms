<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use craft\base\Serializable;
use craft\fields\linktypes\BaseLinkType;
use yii\base\BaseObject;

/**
 * Link field data class.
 *
 * @property-read string $type The link type ID
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class LinkData extends BaseObject implements Serializable
{
    private string $renderedValue;

    /**
     * @param string $value
     * @param string $type
     * @phpstan-param class-string<BaseLinkType> $type
     * @param array $config
     */
    public function __construct(
        private readonly string $value,
        private readonly string $type,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function __toString(): string
    {
        if (!isset($this->renderedValue)) {
            /** @var BaseLinkType|string $type */
            /** @phpstan-var class-string<BaseLinkType> $type */
            $type = $this->type;
            $this->renderedValue = $type::render($this->value);
        }
        return $this->renderedValue;
    }

    public function getType(): string
    {
        return $this->type::id();
    }

    public function serialize(): mixed
    {
        return $this->value;
    }
}
