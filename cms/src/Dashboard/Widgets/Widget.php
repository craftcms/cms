<?php

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Support\Arr;
use DateTimeInterface;

abstract class Widget implements WidgetInterface
{
    public ?int $id = null;

    public ?DateTimeInterface $dateCreated = null;

    public ?DateTimeInterface $dateUpdated = null;

    /**
     * @var int|null The user’s chosen colspan for the widget
     */
    public ?int $colspan = null;

    /**
     * {@inheritdoc}
     */
    public static function isSelectable(): bool
    {
        return static::allowMultipleInstances() || ! app(Dashboard::class)->doesUserHaveWidget(static::class);
    }

    /**
     * Returns whether the widget can be selected more than once.
     *
     * @return bool Whether the widget can be selected more than once
     */
    protected static function allowMultipleInstances(): bool
    {
        return true;
    }

    /**
     * Returns the widget’s SVG icon, if it has one.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     */
    public static function icon(): ?string
    {
        return null;
    }

    /**
     * Returns the widget’s maximum colspan.
     *
     * @return int|null The widget’s maximum colspan, if it has one
     */
    public static function maxColspan(): ?int
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSettingsRules(): array
    {
        return [];
    }

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        $classNameParts = explode('\\', static::class);

        return array_pop($classNameParts);
    }

    /**
     * Returns the widget’s title.
     *
     * @return string|null The widget’s title.
     */
    public function getTitle(): ?string
    {
        // Default to the widget's display name
        return static::displayName();
    }

    /**
     * Returns the widget’s subtitle.
     *
     * @return string|null The widget’s subtitle
     */
    public function getSubtitle(): ?string
    {
        return null;
    }

    /**
     * Returns the widget's body HTML.
     *
     * @return string|null The widget’s body HTML, or `null` if the widget
     *                     should not be visible. (If you don’t want the widget to be selectable in
     *                     the first place, use [[isSelectable()]].)
     */
    public function getBodyHtml(): ?string
    {
        $url = \Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/cp/dist', true, 'images/prg.jpg');

        return <<<EOD
<div style="margin: 0 -24px -24px;">
    <img style="display: block; width: 100%; border-radius: 0 0 4px 4px" src="$url">
</div>
EOD;
    }

    public function getSettingsHtml(): ?string
    {
        return null;
    }

    public function getSettings(): array
    {
        $settings = [];

        $reflectionClass = new \ReflectionClass($this);
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (! $property->isStatic() && ! $property->getDeclaringClass()->isAbstract()) {
                $settings[$property->getName()] = $property->getValue($this);
            }
        }

        return Arr::except($settings, 'id');
    }
}
