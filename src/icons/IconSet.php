<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\icons;

use yii\base\BaseObject;

/**
 * IconSet is the base class for custom icon sets, which can be registered via
 * [[\craft\services\Icons::EVENT_REGISTER_ICON_SETS]].
 *
 * Icons selected from a custom icon set are stored as `set-handle:icon-name`.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
abstract class IconSet extends BaseObject
{
    /**
     * @var string The icon set’s handle, which prefixes the stored values of icons selected from it.
     *
     * Handles must begin with a letter, and can only contain letters, numbers, underscores, and hyphens.
     */
    public string $handle;

    /**
     * @var string The icon set’s name, as it should be shown in the control panel.
     */
    public string $name;

    /**
     * Returns the names of the icons in the set.
     *
     * Icon names can’t contain slashes, backslashes, or angle brackets.
     *
     * @return string[]
     */
    abstract public function getIcons(): array;

    /**
     * Returns search keywords for an icon, in addition to its name.
     *
     * @param string $icon The icon name
     * @return string[]
     */
    public function getKeywords(string $icon): array
    {
        return [];
    }

    /**
     * Returns an icon’s SVG markup, or `null` if the icon doesn’t exist.
     *
     * @param string $icon The icon name
     * @return string|null
     */
    abstract public function getSvg(string $icon): ?string;
}
