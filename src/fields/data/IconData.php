<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use Craft;
use craft\base\Serializable;
use craft\helpers\Cp;
use craft\web\twig\AllowedInSandbox;

/**
 * Class IconData
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class IconData implements Serializable
{
    /**
     * @var array Info about the Font Awesome icons
     * @see getKeywords()
     */
    private static array $_fontAwesomeIcons;

    /**
     * Constructor
     *
     * @param string $name The icon name
     * @param string[] $styles The Font Awesome styles the icon is available in
     * @param string|null $set The handle of the custom icon set the icon belongs to, or `null` for Font Awesome icons
     */
    public function __construct(
        #[AllowedInSandbox]
        public string $name,
        #[AllowedInSandbox]
        public array $styles,
        #[AllowedInSandbox]
        public ?string $set = null,
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
        return $this->set !== null ? "$this->set:$this->name" : $this->name;
    }

    /**
     * Returns the icon’s SVG markup.
     *
     * @return string|null The SVG markup, or `null` if the icon doesn’t exist
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getSvg(): ?string
    {
        return Cp::iconSvg($this->serialize()) ?: null;
    }

    /**
     * Returns the icon’s search keywords.
     *
     * @return string[]
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getKeywords(): array
    {
        if ($this->set !== null) {
            return Craft::$app->getIcons()->getIconSetByHandle($this->set)?->getKeywords($this->name) ?? [];
        }

        if (!isset(self::$_fontAwesomeIcons)) {
            self::$_fontAwesomeIcons = require Craft::getAlias('@app/icons/index.php');
        }

        $terms = self::$_fontAwesomeIcons[$this->name]['terms'] ?? '';
        return array_values(array_filter(explode(' ', $terms), fn(string $term) => $term !== ''));
    }
}
