<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Component\Concerns\SavableComponent;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Models\Widget as WidgetModel;
use CraftCms\Cms\Support\Arr;
use Override;

use function CraftCms\Cms\craftAsset;

/**
 * Provides a base implementation for dashboard widgets.
 */
abstract class Widget extends Component implements WidgetInterface
{
    use ConfigurableComponent;
    use SavableComponent;

    public ?int $colspan = null;

    public function component(): ?string
    {
        return 'craft:html-widget';
    }

    /** @return array<string, mixed>|null */
    public function props(): ?array
    {
        $html = $this->getBodyHtml();

        return $html === null ? null : ['html' => $html];
    }

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

    #[Override]
    public function getType(): string
    {
        return static::class;
    }

    #[Override]
    public function getIcon(): ?string
    {
        return static::icon();
    }

    #[Override]
    public function getDisplayName(): string
    {
        return static::displayName();
    }

    #[Override]
    public function getMaxColspan(): ?int
    {
        return static::maxColspan();
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
        $url = craftAsset('legacy/cp/dist/images/prg.jpg');

        return <<<EOD
<div style="margin: 0 -24px -24px;">
    <img style="display: block; width: 100%; border-radius: 0 0 4px 4px" src="$url">
</div>
EOD;
    }

    #[Override]
    public function validationData(): array
    {
        return $this->getSettings();
    }

    /** @param array<string, mixed>|WidgetModel $config */
    public static function fromConfig(array|WidgetModel $config): WidgetInterface
    {
        if ($config instanceof WidgetModel) {
            $config = $config->toArray();
        }

        $config = Arr::except($config, ['uid', 'userId', 'sortOrder', 'enabled']);

        try {
            return ComponentHelper::createComponent($config, WidgetInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            return new MissingWidget($config);
        }
    }
}
