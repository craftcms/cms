<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

/**
 * @event RegisterCpSettings The event that is triggered when registering links that should render on the Settings page in the control panel.
 *
 * ```php
 * use CraftCms\Cms\Cp\Events\RegisterCpSettings;
 * use Illuminate\Support\Facades\Event;
 * use function \CraftCms\Cms\t;
 *
 * Event::listen(
 *     RegisterCpSettings::class,
 *     function(RegisterCpSettings $e) {
 *         $e->settings[t('Modules')][] = [
 *             'label' => 'Item Label',
 *             'url' => 'my-module',
 *             'icon' => '/path/to/icon.svg',
 *         ];
 *     }
 * );
 * ```
 *
 * [[RegisterCpSettings::$settings]] is an array whose keys define the section labels, and values are sub-arrays that define the
 * individual links.
 *
 * Each link array should have the following keys:
 *
 * - `label` – The item’s label.
 * - `url` – The URL or path of the control panel page the item should link to.
 * - `icon` – The path to the SVG icon that should be used for the item.
 */
final class RegisterCpSettings
{
    public function __construct(
        /** @var array $settings The registered control panel settings */
        public array $settings = []
    ) {}
}
