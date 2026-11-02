<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\RegisterIconSetsEvent;
use CraftCms\Cms\Cp\Events\IconSetsResolving;
use CraftCms\Cms\Cp\IconSets;
use CraftCms\Cms\Cp\IconSets\IconSet;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * Icons service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getIcons()|`Craft::$app->getIcons()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.12.0
 * @deprecated in 6.0.0. {@see \CraftCms\Cms\Cp\IconSets} should be used instead.
 */
class Icons extends Component
{
    /**
     * @event RegisterIconSetsEvent The event that is triggered when registering custom icon sets.
     *
     * ```php
     * use craft\events\RegisterIconSetsEvent;
     * use craft\services\Icons;
     * use yii\base\Event;
     *
     * Event::on(
     *     Icons::class,
     *     Icons::EVENT_REGISTER_ICON_SETS,
     *     function(RegisterIconSetsEvent $event) {
     *         $event->iconSets[] = new MyIconSet([
     *             'handle' => 'my-icons',
     *             'name' => 'My Icons',
     *         ]);
     *     }
     * );
     * ```
     *
     * @deprecated in 6.0.0. Listen for {@see IconSetsResolving} instead.
     */
    public const EVENT_REGISTER_ICON_SETS = 'registerIconSets';

    /**
     * @var string The handle that refers to the built-in Font Awesome icons.
     */
    public const FONT_AWESOME = IconSets::FONT_AWESOME;

    /**
     * @var string The handle of the icon set registered via the <config5:customIconPath> config setting.
     */
    public const CUSTOM = IconSets::CUSTOM;

    public static function registerEvents(): void
    {
        Event::listen(function(IconSetsResolving $event) {
            $service = Craft::$app->getIcons();

            if (!$service->hasEventHandlers(self::EVENT_REGISTER_ICON_SETS)) {
                return;
            }

            $yiiEvent = new RegisterIconSetsEvent(['iconSets' => $event->iconSets]);
            $service->trigger(self::EVENT_REGISTER_ICON_SETS, $yiiEvent);
            $event->iconSets = array_values($yiiEvent->iconSets);
        });
    }

    /**
     * Parses an icon value into its set handle and icon name, if it references a custom icon set.
     *
     * @return array{string, string}|null
     */
    public static function parse(string $value): ?array
    {
        return IconSets::parse($value);
    }

    /**
     * Returns all registered custom icon sets, indexed by their handles.
     *
     * @return array<string, IconSet>
     */
    public function getIconSets(): array
    {
        return app(IconSets::class)->getIconSets();
    }

    /**
     * Returns a custom icon set by its handle.
     */
    public function getIconSetByHandle(string $handle): ?IconSet
    {
        return app(IconSets::class)->getIconSetByHandle($handle);
    }

    /**
     * Returns the names of all available icon sets, including Font Awesome, indexed by their handles.
     *
     * @return array<string, string>
     */
    public function getIconSetNames(): array
    {
        return app(IconSets::class)->getIconSetNames();
    }
}
