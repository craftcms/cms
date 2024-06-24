<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use craft\fields\Link;
use yii\base\BaseObject;

/**
 * Base link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
abstract class BaseLinkType extends BaseObject
{
    /**
     * Returns the link type’s unique identifier, which will be stored within
     * Link fields’ [[\craft\fields\Link::types]] settings.
     *
     * @return string
     */
    abstract public static function id(): string;

    /**
     * Returns the link type’s human-facing label.
     *
     * @return string
     */
    abstract public static function label(): string;

    /**
     * Returns whether the given value is supported by this link type.
     *
     * @param string $value
     * @return bool
     */
    abstract public static function supports(string $value): bool;

    /**
     * Normalizes a posted link value.
     *
     * @param string $value
     * @return string
     */
    public static function normalize(string $value): string
    {
        return $value;
    }

    /**
     * Renders a value for the front end.
     *
     * @param string $value
     * @return string
     */
    public static function render(string $value): string
    {
        return $value;
    }

    /**
     * Returns the input HTML that should be shown when this link type is selected.
     *
     * @param Link $field The Link field
     * @param string|null $value The current value, if this link type was previously selected.
     * @param string $containerId The ID of the input’s container div.
     * @return string
     */
    abstract public static function inputHtml(Link $field, ?string $value, string $containerId): string;

    /**
     * Validates the given value.
     *
     * @param string $value
     * @param string|null $error
     * @return bool
     */
    abstract public static function validate(string $value, ?string &$error = null): bool;
}
