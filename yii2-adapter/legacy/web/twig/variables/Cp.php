<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\events\FormActionsEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterCpSettingsEvent;
use craft\helpers\Cp as CpHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\twig\TemplateLoaderException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Events\RegisterCpNavItems;
use CraftCms\Cms\Cp\Events\RegisterCpSettings;
use CraftCms\Cms\Cp\Events\RegisterReadonlyCpSettings;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utility;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use function CraftCms\Cms\t;

/**
 * Control panel functions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
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
     * @see EVENT_REGISTER_CP_SETTINGS
     * @since 5.6.0
     */
    public const EVENT_REGISTER_READ_ONLY_CP_SETTINGS = 'registerReadOnlyCpSettings';

    /**
     * Returns the site the control panel is currently working with, via a `site` query string param if sent.
     *
     * @return Site|null The site, or `null` if the user doesn’t have permission to edit any sites.
     * @since 4.0.4
     */
    public function getRequestedSite(): ?Site
    {
        return CpHelper::requestedSite();
    }

    /**
     * Returns the Craft Console account URL.
     *
     * @return string
     */
    public function craftIdAccountUrl(): string
    {
        return Api::craftIdEndpoint() . '/account';
    }

    /**
     * Returns the control panel nav items.
     *
     * Each control panel nav item should be defined by an array with the following keys:
     *
     * - `label` – The human-facing nav item label
     * - `url` – The URL the nav item should link to
     * - `id` – The HTML `id` attribute the nav item should have (optional)
     * - `icon` – The icon name or a path to an SVG file that should be used as the nav item icon (optional)
     * - `fontIcon` – A character/ligature from Craft’s font icon set (optional)
     * - `badgeCount` – A number that should be displayed beside the nav item when unselected
     * - `subnav` – A sub-array of subnav items
     *
     * Subnav arrays should be associative, with identifiable keys set to sub-arrays with the following keys:
     *
     * - `label` – The human-facing subnav item label
     * - `url` – The URL the subnav item should link to
     * - `icon` – The icon name or a path to an SVG file that should be used as the nav item icon (optional)
     * - `fontIcon` – A character/ligature from Craft’s font icon set (optional)
     *
     * For example:
     *
     * ```php
     * [
     *     'label' => 'Commerce',
     *     'url' => 'commerce',
     *     'subnav' => [
     *         'orders' => ['label' => 'Orders', 'url' => 'commerce/orders',
     *         'discounts' => ['label' => 'Discounts', 'url' => 'commerce/discounts',
     *     ],
     * ]
     * ```
     *
     * Control panel templates can specify which subnav item is selected by defining a `selectedSubnavItem` variable.
     *
     * ```twig
     * {% set selectedSubnavItem = 'orders' %}
     * ```
     *
     * @return array
     * @throws InvalidConfigException
     */
    public function nav(): array
    {
        $isAdmin = Auth::user()?->isAdmin();
        $generalConfig = Cms::config();

        $navItems = [
            [
                'label' => t('Dashboard'),
                'url' => 'dashboard',
                'icon' => 'gauge',
            ],
        ];

        if (Sections::getTotalEditableSections()) {
            $elementSourcesService = app(ElementSources::class);
            $entryPages = $elementSourcesService->getPages(Entry::class);

            if ($entryPages->isNotEmpty()) {
                $entryPageSettings = $elementSourcesService->getPageSettings(Entry::class);
                foreach ($entryPages as $page) {
                    $navItems[] = [
                        'label' => $page !== 'Entries' ? t($page, category: 'site') : t('Entries'),
                        'url' => sprintf('content/%s', Str::slug($page)),
                        'icon' => $entryPageSettings[$page]['icon'] ?? 'newspaper',
                    ];
                }
            } else {
                $navItems[] = [
                    'label' => t('Entries'),
                    'url' => 'content/entries',
                    'icon' => 'newspaper',
                ];
            }
        }

        if (Craft::$app->getVolumes()->getTotalViewableVolumes()) {
            $navItems[] = [
                'label' => t('Assets'),
                'url' => 'assets',
                'icon' => 'image',
            ];
        }

        if (
            Edition::get() !== Edition::Solo &&
            Gate::check('viewUsers')
        ) {
            $navItems[] = [
                'label' => t('Users'),
                'url' => 'users',
                'icon' => 'user-group',
            ];
        }

        // Add any Plugin nav items
        $plugins = app(Plugins::class)->getAllPlugins();

        foreach ($plugins as $plugin) {
            if (
                $plugin->hasCpSection &&
                Gate::check('accessPlugin-' . $plugin->handle) &&
                ($pluginNavItem = $plugin->getCpNavItem()) !== null
            ) {
                $navItems[] = $pluginNavItem;
            }
        }

        if ($isAdmin) {
            if ($generalConfig->enableGql) {
                $subNavItems = [];

                if ($generalConfig->allowAdminChanges) {
                    $subNavItems['schemas'] = [
                        'label' => t('Schemas'),
                        'url' => 'graphql/schemas',
                    ];
                }

                $subNavItems['tokens'] = [
                    'label' => t('Tokens'),
                    'url' => 'graphql/tokens',
                ];

                $subNavItems['graphiql'] = [
                    'label' => 'GraphiQL',
                    'url' => 'graphiql',
                    'external' => true,
                ];

                $navItems[] = [
                    'label' => 'GraphQL',
                    'url' => 'graphql',
                    'icon' => 'graphql',
                    'subnav' => $subNavItems,
                ];
            }
        }

        $utilities = app(Utilities::class)->getAuthorizedUtilityTypes();

        if (!empty($utilities)) {
            $badgeCount = 0;

            foreach ($utilities as $class) {
                /** @var Utility $class */
                $badgeCount += $class::badgeCount();
            }

            $navItems[] = [
                'url' => 'utilities',
                'label' => t('Utilities'),
                'icon' => 'wrench',
                'badgeCount' => $badgeCount,
            ];
        }

        if ($isAdmin) {
            $navItems[] = [
                'url' => 'settings',
                'label' => t('Settings'),
                'icon' => Cms::config()->allowAdminChanges ? 'gear' : 'gear-slash',
            ];

            $navItems[] = [
                'url' => 'plugin-store',
                'label' => t('Plugin Store'),
                'icon' => 'plug',
            ];
        }

        event($event = new RegisterCpNavItems($navItems));

        $navItems = $event->navItems;

        // Figure out which item is selected, and normalize the items
        $path = Craft::$app->getRequest()->getPathInfo();

        if ($path === 'myaccount' || str_starts_with($path, 'myaccount/')) {
            $path = 'users';
        }

        $foundSelectedItem = false;

        foreach ($navItems as &$item) {
            if (!$foundSelectedItem && ($item['url'] == $path || str_starts_with($path, $item['url'] . '/'))) {
                $item['sel'] = true;
                $foundSelectedItem = true;

                // Modify aria-current value for exact page vs. subpages
                $item['linkAttributes']['aria']['current'] = $item['url'] === $path ? 'page' : 'true';
            } else {
                $item['sel'] = false;
            }

            if (!isset($item['subnav'])) {
                $item['subnav'] = false;
            }

            if (!isset($item['id'])) {
                $item['id'] = 'nav-' . preg_replace('/[^\w\-_]/', '', Str::ascii(str_replace('/', '-', $item['url'])));
            }

            $item['url'] = UrlHelper::url($item['url']);

            if (!isset($item['external'])) {
                $item['external'] = false;
            }

            if (!isset($item['badgeCount'])) {
                $item['badgeCount'] = 0;
            }
        }

        return $navItems;
    }

    /**
     * Returns whether the control panel alerts are cached.
     *
     * @return bool
     */
    public function areAlertsCached(): bool
    {
        // The license key status gets cached on each Craftnet request
        return !is_null(Cache::get(License::CACHE_KEY_LICENSE_INFO));
    }

    /**
     * Returns an array of alerts to display in the control panel.
     *
     * @return array
     */
    public function getAlerts(): array
    {
        return CpHelper::alerts(Craft::$app->getRequest()->getPathInfo());
    }

    /**
     * Returns info about the active trials.
     *
     * @return array|null
     * @internal
     */
    public function trialInfo(): ?array
    {
        $issues = Collection::make(app(License::class)->issues([
            LicenseKeyStatus::Trial->value,
            LicenseKeyStatus::Astray->value,
            'wrong_edition',
        ]));

        if ($issues->isEmpty()) {
            return null;
        }

        $cmsIssues = $issues->filter(fn($issue) => in_array($issue[2]['type'], ['cms-edition', 'cms-renewal']));
        $pluginEditionIssues = $issues->filter(fn($issue) => $issue[2]['type'] === 'plugin-edition');
        $pluginRenewalIssues = $issues->filter(fn($issue) => $issue[2]['type'] === 'plugin-renewal');

        $names = $cmsIssues->map(fn($issue) => $issue[0])->all();
        foreach ([$pluginEditionIssues, $pluginRenewalIssues] as $group) {
            /** @var Collection $group */
            $count = $group->count();
            if ($count === 1) {
                $names[] = $group->first()[0];
            } elseif ($count !== 0) {
                if ($group->first()[2]['type'] === 'plugin-edition') {
                    $name = t('{count, spellout} {count, plural, =1{plugin} other{plugins}}', [
                        'count' => $count,
                    ]);
                } else {
                    $name = t('{count, spellout} plugin {count, plural, =1{update} other{updates}}', [
                        'count' => $count,
                    ]);
                }
                if (empty($names)) {
                    $name = ucfirst($name);
                }
                $names[] = $name;
            }
        }

        $message = t('{names} {total, plural, =1{is installed as a trial} other{are installed as trials}}.', [
            'names' => collect($names)->sentence(),
            'total' => $issues->count(),
        ]);

        $consoleUrl = rtrim(Api::craftIdEndpoint(), '/');
        $cartUrl = UrlHelper::urlWithParams("$consoleUrl/cart/new", [
            'items' => $issues->map(fn($issue) => $issue[2])->all(),
        ]);

        return [
            'message' => $message,
            'cartUrl' => $cartUrl,
        ];
    }

    /**
     * Returns the available environment variable and alias suggestions for
     * inputs that support them.
     *
     * @param bool $includeAliases Whether aliases should be included in the list
     * (only enable this if the setting defines a URL or file path)
     * @param callable|null $filter A function that returns whether a given value should be included
     * @phpstan-param callable(scalar):bool|null $filter
     * @return array[]
     * @phpstan-return array{label:string,data:array}[]
     * @deprecated in 6.0.0.  [[\CraftCms\Cms\Cp\SelectOptions::getEnvSuggestions]] should be used instead.
     * @since 3.1.0
     */
    public function getEnvSuggestions(bool $includeAliases = false, ?callable $filter = null): array
    {
        return $this->formatLegacySuggestions(SelectOptions::getEnvSuggestions($includeAliases, $filter));
    }

    /**
     * Returns environment variable options for a select input.
     *
     * @param array|null $allowedValues
     * @return array
     * @since 3.7.22
     * @deprecated in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getEnvOptions] should be used instead.
     */
    public function getEnvOptions(?array $allowedValues = null): array
    {
        return $this->formatLegacyOptions(SelectOptions::getEnvOptions($allowedValues));
    }

    /**
     * Returns environment variable options for a boolean menu.
     *
     * @return array
     * @since 3.7.22
     * @deprecated  in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getBooleanEnvOptions] should be used instead.
     */
    public function getBooleanEnvOptions(): array
    {
        return $this->formatLegacyOptions(SelectOptions::getBooleanEnvOptions());
    }

    /**
     * Returns environment variable options for a language menu.
     *
     * @param bool $appOnly Whether to limit the env options to those that match available app locales
     * @return array
     * @since 5.0.0
     * @deprecated  in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getLanguageEnvOptions]] shoudl be used instead.
     */
    public function getLanguageEnvOptions(bool $appOnly = false): array
    {
        return $this->formatLegacyOptions(SelectOptions::getLanguageEnvOptions($appOnly));
    }

    /**
     * Returns all known time zones for a time zone input.
     *
     * @param DateTime|null $offsetDate The [[DateTime]] object that contains the date/time to compute time zone offsets from
     * @return array
     * @since 3.7.0
     * @deprecated in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getTimezoneOptions]] should be used instead.
     */
    public function getTimeZoneOptions(?DateTime $offsetDate = null): array
    {
        return $this->formatLegacyOptions(SelectOptions::getTimeZoneOptions($offsetDate));
    }

    /**
     * Returns all known language options for a language input.
     *
     * @param bool $showLocaleIds Whether to show the hint as locale id; e.g. en, en-GB
     * @param bool $showLocalizedNames Whether to show the hint as localizes names; e.g. English, English (United Kingdom)
     * @param bool $appLocales Whether to limit the returned locales to just app locales (cp translation options) or show them all
     * @return array
     * @since 5.0.0
     * @deprecated in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getLanguageOptions]] should be used instead.
     */
    public function getLanguageOptions(
        bool $showLocaleIds = false,
        bool $showLocalizedNames = false,
        bool $appLocales = false,
    ): array {
        return array_map(function($locale) {
            return [
                'label' => $locale['label'],
                'value' => $locale['value'],
                'data' => [
                    'data' => array_filter([
                        'keywords' => $locale['data']['keywords'] ?? null,
                        'hintLang' => $locale['data']['lang'] ?? null,
                        'hint' => $locale['data']['hint'] ?? null,
                    ]),
                ],
            ];
        }, SelectOptions::getLanguageOptions($showLocaleIds, $showLocalizedNames, $appLocales));
    }

    /**
     * Returns all options for a filesystem input.
     *
     * @return array
     * @since 4.0.0
     * @deprecated in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getFsOptions]] should be used instead.
     */
    public function getFsOptions(): array
    {
        return SelectOptions::getFsOptions();
    }

    /**
     * Returns all options for a volume input.
     *
     * @return array
     * @since 4.0.0
     * @deprecated in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getVolumeOptions]] should be used instead.
     */
    public function getVolumeOptions(): array
    {
        return SelectOptions::getVolumeOptions();
    }

    /**
     * Returns ASCII character mappings for the given language, if it differs from the application language.
     *
     * @param string $language
     * @return array|null
     * @since 3.1.9
     */
    public function getAsciiCharMap(string $language): ?array
    {
        if ($language === app()->getLocale()) {
            return null;
        }

        return Str::asciiCharMap(true, $language);
    }

    /**
     * Returns the available template path suggestions for template inputs.
     *
     * @return array[]
     * @phpstan-return array{label:string,data:array}[]
     * @since 3.1.0
     */
    public function getTemplateSuggestions(): array
    {
        // Get all the template files sorted by path length
        $roots = Arr::merge([
            '' => [Craft::$app->getPath()->getSiteTemplatesPath()],
        ], Craft::$app->getView()->getSiteTemplateRoots());

        $suggestions = [];
        $templates = [];
        $sites = [];

        foreach (Sites::getAllSites() as $site) {
            $sites[$site->handle] = t($site->getName(), category: 'site');
        }

        foreach ($roots as $root => $basePaths) {
            foreach ($basePaths as $basePath) {
                if (!is_dir($basePath)) {
                    continue;
                }

                $directory = new RecursiveDirectoryIterator($basePath);

                $filter = new RecursiveCallbackFilterIterator($directory, function($current) {
                    // Skip hidden files and directories, as well as node_modules/ folders
                    if ($current->getFilename()[0] === '.' || $current->getFilename() === 'node_modules') {
                        return false;
                    }
                    return true;
                });

                $iterator = new RecursiveIteratorIterator($filter);
                /** @var SplFileInfo[] $files */
                $files = [];
                $pathLengths = [];

                foreach ($iterator as $file) {
                    /** @var SplFileInfo $file */
                    if (!$file->isDir() && $file->getFilename()[0] !== '.') {
                        $files[] = $file;
                        $pathLengths[] = strlen($file->getRealPath());
                    }
                }

                array_multisort($pathLengths, SORT_NUMERIC, $files);

                $basePathLength = strlen($basePath);

                foreach ($files as $file) {
                    $template = substr($file->getRealPath(), $basePathLength + 1);
                    $hint = null;

                    // Is it in a site template directory?
                    foreach ($sites as $handle => $name) {
                        if (str_starts_with($template, $handle . DIRECTORY_SEPARATOR)) {
                            $hint = $name;
                            $template = substr($template, strlen($handle) + 1);
                            break;
                        }
                    }

                    // Prepend the template root path
                    if ($root !== '') {
                        $template = sprintf('%s/%s', $root, $template);
                    }

                    // Avoid listing the same template path twice (considering localized templates)
                    if (isset($templates[$template])) {
                        continue;
                    }

                    $templates[$template] = true;
                    $suggestions[] = [
                        'name' => $template,
                        'hint' => $hint,
                    ];
                }
            }
        }

        return [
            [
                'label' => t('Templates'),
                'data' => array_values(Arr::sort($suggestions, 'name')),
            ],
        ];
    }

    /**
     * Prepares form actions
     *
     * @param array|null $formActions
     * @return array|null
     * @since 3.6.10
     */
    public function prepFormActions(?array $formActions): ?array
    {
        // Fire a 'registerFormActions' event
        if ($this->hasEventHandlers(self::EVENT_REGISTER_FORM_ACTIONS)) {
            $event = new FormActionsEvent([
                'formActions' => $formActions ?? [],
            ]);
            $this->trigger(self::EVENT_REGISTER_FORM_ACTIONS, $event);
            return $event->formActions ?: null;
        }

        return $formActions;
    }

    /**
     * Renders a field’s HTML, for the given input HTML or a template.
     *
     * @param string $input The input HTML or template path. If passing a template path, it must begin with `template:`.
     * @param array $config
     * @return string
     * @throws TemplateLoaderException if $input begins with `template:` and is followed by an invalid template path
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     * @since 3.7.24
     */
    public function field(string $input, array $config = []): string
    {
        return CpHelper::fieldHtml($input, $config);
    }

    /**
     * Renders a field layout designer’s HTML.
     *
     * @param FieldLayout $fieldLayout
     * @param array $config
     * @return string
     * @since 4.0.0
     * @deprecated in 5.5.0. The `fieldLayoutDesigner()` global CP function should be used instead.
     */
    public function fieldLayoutDesigner(FieldLayout $fieldLayout, array $config = []): string
    {
        return CpHelper::fieldLayoutDesignerHtml($fieldLayout, $config);
    }

    public static function registerEvents(): void
    {
        Event::listen(RegisterCpSettings::class, function(RegisterCpSettings $event) {
            if (\yii\base\Event::hasHandlers(Cp::class, self::EVENT_REGISTER_CP_SETTINGS)) {
                $yiiEvent = new RegisterCpSettingsEvent(['settings' => &$event->settings]);

                \yii\base\Event::trigger(Cp::class, self::EVENT_REGISTER_CP_SETTINGS, $yiiEvent);
            }
        });

        Event::listen(RegisterReadonlyCpSettings::class, function(RegisterReadonlyCpSettings $event) {
            if (\yii\base\Event::hasHandlers(Cp::class, self::EVENT_REGISTER_READ_ONLY_CP_SETTINGS)) {
                $yiiEvent = new RegisterCpSettingsEvent(['settings' => &$event->settings]);

                \yii\base\Event::trigger(Cp::class, self::EVENT_REGISTER_READ_ONLY_CP_SETTINGS, $yiiEvent);
            }
        });
    }

    private function formatLegacySuggestions(array $options): array
    {
        return array_map(function($group) {
            return [
                'label' => $group['label'],
                'data' => array_map(function(array $option) {
                    return [
                        'name' => $option['label'],
                        'hint' => $option['data']['hint'] ?? null,
                    ];
                }, $group['options']),
            ];
        }, $options);
    }

    private function formatLegacyOptions(array $originalOptions): array
    {
        $options = [];

        foreach ($originalOptions as $value) {
            if ($value['type'] === 'optgroup') {
                $options[] = ['optgroup' => $value['label']];
                array_push($options, ...($value['options'] ?? []));
            } else {
                $options[] = [
                    'label' => $value['label'],
                    'value' => $value['value'],
                    'data' => $value['data'],
                ];
            }
        }

        return $options;
    }
}
