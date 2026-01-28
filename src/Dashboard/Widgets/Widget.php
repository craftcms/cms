<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\helpers\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Component\Concerns\SavableComponent;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Models\Widget as WidgetModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Validation\Concerns\Validates;
use Override;
use RuntimeException;

/**
 * Provides a base implementation for dashboard widgets.
 */
abstract class Widget implements WidgetInterface
{
    use ConfigurableComponent;
    use SavableComponent;
    use Validates;

    public ?int $colspan = null;

    public function __construct(array $config = [])
    {
        Typecast::properties(static::class, $config);

        foreach ($config as $name => $value) {
            if (! property_exists($this, $name)) {
                continue;
            }

            $this->$name = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function isSelectable(): bool
    {
        if (static::allowMultipleInstances()) {
            return true;
        }

        return ! app(Dashboard::class)->doesUserHaveWidget(static::class);
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
    #[Override]
    public static function icon(): ?string
    {
        return null;
    }

    /**
     * Returns the widget’s maximum colspan.
     *
     * @return int|null The widget’s maximum colspan, if it has one
     */
    #[Override]
    public static function maxColspan(): ?int
    {
        return null;
    }

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    #[Override]
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
    #[Override]
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
    #[Override]
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
    #[Override]
    public function getBodyHtml(): ?string
    {
        $url = Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/cp/dist', true, 'images/prg.jpg');

        return <<<EOD
<div style="margin: 0 -24px -24px;">
    <img style="display: block; width: 100%; border-radius: 0 0 4px 4px" src="$url">
</div>
EOD;
    }

    #[Override]
    public function getAttributes(): array
    {
        return $this->getSettings();
    }

    public function attributes(): array
    {
        return array_keys($this->getSettings());
    }

    public static function fromConfig(array|WidgetModel $config): WidgetInterface
    {
        if ($config instanceof WidgetModel) {
            $config = $config->toArray();
        }

        $class = Arr::pull($config, 'type');
        $config = Arr::except($config, ['uid', 'userId', 'sortOrder', 'enabled']);

        if (! $class || ! Component::validateComponentClass($class, WidgetInterface::class)) {
            throw new RuntimeException('The config passed into Widget::fromConfig() did not specify a valid type: '.Json::encode($config));
        }

        $config = Component::mergeSettings($config);

        return app()->make($class, ['config' => $config]);
    }
}
