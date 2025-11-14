<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use craft\base\ConfigurableComponent;
use craft\fields\Link;

/**
 * Base link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
abstract class BaseLinkType extends ConfigurableComponent
{
    /**
     * Returns the link type’s unique identifier, which will be stored within
     * Link fields’ [[\craft\fields\Link::types]] settings.
     *
     * @return string
     */
    abstract public static function id(): string;

    /**
     * Returns whether the given value is supported by this link type.
     *
     * @param string $value
     * @return bool
     */
    abstract public function supports(string $value): bool;

    /**
     * Normalizes a posted link value.
     *
     * @param string $value
     * @return string
     */
    public function normalizeValue(string $value): string
    {
        return $value;
    }

    /**
     * Renders a value for the front end.
     *
     * @param string $value
     * @return string
     */
    public function renderValue(string $value): string
    {
        return $value;
    }

    /**
     * Returns the default link label for [[\craft\fields\data\LinkData::getLabel()]].
     *
     * @return string
     */
    abstract public function linkLabel(string $value): string;

    /**
     * Returns the default download filename for [[\craft\fields\data\LinkData::getFilename()]].
     *
     * @return string|null
     * @since 5.7.0
     */
    public function filename(string $value): ?string
    {
        return null;
    }

    /**
     * Returns the input HTML that should be shown when this link type is selected.
     *
     * @param Link $field The Link field
     * @param string|null $value The current value, if this link type was previously selected.
     * @param string $containerId The ID of the input’s container div.
     * @return string
     */
    abstract public function inputHtml(Link $field, ?string $value, string $containerId): string;

    /**
     * Validates the given value.
     *
     * @param string $value
     * @param string|null $error
     * @return bool
     */
    abstract public function validateValue(string $value, ?string &$error = null): bool;

    /**
     * Returns whether given value is empty.
     *
     * @param string $value
     * @return bool
     * @since 5.7.11
     */
    public function isValueEmpty(string $value): bool
    {
        return empty($value);
    }
}
