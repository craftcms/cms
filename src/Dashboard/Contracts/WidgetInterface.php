<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Contracts;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Component\Contracts\SavableComponentInterface;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Validation\Contracts\Validatable;

/**
 * WidgetInterface defines the common interface to be implemented by dashboard widget classes.
 * A class implementing this interface should extend {@see Widget}.
 */
interface WidgetInterface extends ConfigurableComponentInterface, SavableComponentInterface, Validatable
{
    public ?int $colspan { get; set; }

    /**
     * Returns the widget’s SVG icon, if it has one.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     */
    public static function icon(): ?string;

    /**
     * Returns the widget’s maximum colspan.
     *
     * @return int|null The widget’s maximum colspan, if it has one
     */
    public static function maxColspan(): ?int;

    public function getType(): string;

    public function getIcon(): ?string;

    public function getDisplayName(): string;

    public function getMaxColspan(): ?int;

    /**
     * Returns the widget’s title.
     *
     * @return string|null The widget’s title.
     */
    public function getTitle(): ?string;

    /**
     * Returns the widget’s subtitle.
     *
     * @return string|null The widget’s subtitle
     */
    public function getSubtitle(): ?string;

    /**
     * Returns the widget's body HTML.
     *
     * @return string|null The widget’s body HTML, or `null` if the widget
     *                     should not be visible. (If you don’t want the widget to be selectable in
     *                     the first place, use [[isSelectable()]].)
     */
    public function getBodyHtml(): ?string;
}
