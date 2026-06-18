<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\base\Event as YiiEvent;
use craft\events\FormActionsEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterCpSettingsEvent;
use CraftCms\Cms\Cp\Events\CpNavItemsResolving;
use CraftCms\Cms\Cp\Events\FormActionsResolving;
use CraftCms\Cms\Cp\Events\RegisterCpSettings;
use CraftCms\Cms\Cp\Events\RegisterReadonlyCpSettings;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * Control panel functions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Twig\Variables\Cp} instead.
 */
class Cp extends Component
{
    /**
     * @event FormActionsEvent The event that is triggered when preparing the page’s form actions.
     *
     * ```php
     * use craft\events\FormActionsEvent;
     * use craft\web\twig\variables\Cp;
     * use yii\base\Event;
     *
     * Event::on(
     *     Cp::class,
     *     Cp::EVENT_REGISTER_FORM_ACTIONS,
     *     function(FormActionsEvent $event) {
     *         if (Craft::$app->requestedRoute == 'entries/edit-entry') {
     *             $event->formActions[] = [
     *                 'label' => 'Save and view entry',
     *                 'redirect' => Craft::$app->getSecurity()->hashData('{url}'),
     *             ];
     *         }
     *     }
     * );
     * ```
     *
     * @see prepFormActions()
     * @since 3.6.10
     */
    public const EVENT_REGISTER_FORM_ACTIONS = 'registerFormActions';

    /**
     * @event RegisterCpNavItemsEvent The event that is triggered when registering control panel nav items.
     *
     * ```php
     * use craft\events\RegisterCpNavItemsEvent;
     * use craft\web\twig\variables\Cp;
     * use yii\base\Event;
     *
     * Event::on(
     *     Cp::class,
     *     Cp::EVENT_REGISTER_CP_NAV_ITEMS,
     *     function(RegisterCpNavItemsEvent $e) {
     *         $e->navItems[] = [
     *             'label' => 'Item Label',
     *             'url' => 'my-module',
     *             'icon' => '/path/to/icon.svg',
     *         ];
     *     }
     * );
     * ```
     *
     * [[RegisterCpNavItemsEvent::$navItems]] is an array whose values are sub-arrays that define the nav items. Each sub-array can have the following keys:
     *
     * - `label` – The item’s label.
     * - `url` – The URL or path of the control panel page the item should link to.
     * - `icon` – The path to the SVG icon that should be used for the item.
     * - `badgeCount` _(optional)_ – The badge count number that should be displayed next to the label.
     * - `external` _(optional)_ – Set to `true` if the item links to an external URL.
     * - `id` _(optional)_ – The ID of the `<li>` element. If not specified, it will default to `nav-`.
     * - `subnav` _(optional)_ – A nested array of sub-navigation items that should be displayed if the main item is selected.
     *
     *   The keys of the array should define the items’ IDs, and the values should be nested arrays with `label` and `url` keys, and optionally
     *   `badgeCount` and `external` keys.
     *
     * If a subnav is defined, subpages can specify which subnav item should be selected by defining a `selectedSubnavItem` variable that is set to
     * the selected item’s ID (its key in the `subnav` array).
     */
    public const EVENT_REGISTER_CP_NAV_ITEMS = 'registerCpNavItems';

    /**
     * @event RegisterCpSettingsEvent The event that is triggered when registering links that should render on the Settings page in the control panel.
     *
     * ```php
     * use craft\events\RegisterCpSettingsEvent;
     * use craft\web\twig\variables\Cp;
     * use yii\base\Event;
     *
     * Event::on(
     *     Cp::class,
     *     Cp::EVENT_REGISTER_CP_SETTINGS,
     *     function(RegisterCpSettingsEvent $e) {
     *         $e->settings[\CraftCms\Cms\t('Modules')][] = [
     *             'label' => 'Item Label',
     *             'url' => 'my-module',
     *             'icon' => '/path/to/icon.svg',
     *         ];
     *     }
     * );
     * ```
     *
     * [[RegisterCpSettingsEvent::$settings]] is an array whose keys define the section labels, and values are sub-arrays that define the
     * individual links.
     *
     * Each link array should have the following keys:
     *
     * - `label` – The item’s label.
     * - `url` – The URL or path of the control panel page the item should link to.
     * - `icon` – The path to the SVG icon that should be used for the item.
     *
     * @since 3.1.0
     */
    public const EVENT_REGISTER_CP_SETTINGS = 'registerCpSettings';

    /**
     * @event RegisterCpSettingsEvent The event that is triggered when registering links that should render on the
     * Settings page in the control panel, when admin changes are disallowed.
     *
     * @see EVENT_REGISTER_CP_SETTINGS
     * @since 5.6.0
     */
    public const EVENT_REGISTER_READ_ONLY_CP_SETTINGS = 'registerReadOnlyCpSettings';

    public static function registerEvents(): void
    {
        \CraftCms\Cms\Twig\Variables\Cp::macro('fieldLayoutDesigner', function(FieldLayout $fieldLayout, array $config = []) {
            return app(FieldLayoutDesigner::class)->html($fieldLayout, $config);
        });

        Event::listen(function(RegisterCpSettings $event) {
            if (\yii\base\Event::hasHandlers(self::class, self::EVENT_REGISTER_CP_SETTINGS)) {
                $yiiEvent = new RegisterCpSettingsEvent(['settings' => $event->settings]);

                \yii\base\Event::trigger(self::class, self::EVENT_REGISTER_CP_SETTINGS, $yiiEvent);

                $event->settings = $yiiEvent->settings;
            }
        });

        Event::listen(function(RegisterReadonlyCpSettings $event) {
            if (\yii\base\Event::hasHandlers(self::class, self::EVENT_REGISTER_READ_ONLY_CP_SETTINGS)) {
                $yiiEvent = new RegisterCpSettingsEvent(['settings' => $event->settings]);

                \yii\base\Event::trigger(self::class, self::EVENT_REGISTER_READ_ONLY_CP_SETTINGS, $yiiEvent);

                $event->settings = $yiiEvent->settings;
            }
        });

        Event::listen(function(CpNavItemsResolving $event) {
            if (YiiEvent::hasHandlers(self::class, 'registerCpNavItems')) {
                $yiiEvent = new RegisterCpNavItemsEvent(['navItems' => $event->navItems]);

                YiiEvent::trigger(self::class, 'registerCpNavItems', $yiiEvent);

                $event->navItems = $yiiEvent->navItems;
            }
        });

        Event::listen(function(FormActionsResolving $event) {
            if (\yii\base\Event::hasHandlers(self::class, self::EVENT_REGISTER_FORM_ACTIONS)) {
                $yiiEvent = new FormActionsEvent(['formActions' => $event->formActions]);

                \yii\base\Event::trigger(self::class, self::EVENT_REGISTER_FORM_ACTIONS, $yiiEvent);

                $event->formActions = $yiiEvent->formActions;
            }
        });
    }
}
