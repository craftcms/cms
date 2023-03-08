<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\base\FsInterface;
use craft\base\UtilityInterface;
use craft\events\FormActionsEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterCpSettingsEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp as CpHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\Site;
use craft\models\Volume;
use craft\web\twig\TemplateLoaderException;
use DateTime;
use DateTimeZone;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

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
     *         $e->settings[Craft::t('app', 'Modules')][] = [
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
        return Craft::$app->getPluginStore()->craftIdEndpoint . '/account';
    }

    /**
     * Returns the control panel nav items.
     *
     * Each control panel nav item should be defined by an array with the following keys:
     *
     * - `label` – The human-facing nav item label
     * - `url` – The URL the nav item should link to
     * - `id` – The HTML `id` attribute the nav item should have (optional)
     * - `icon` – The path to an SVG file that should be used as the nav item icon (optional)
     * - `fontIcon` – A character/ligature from Craft’s font icon set (optional)
     * - `badgeCount` – A number that should be displayed beside the nav item when unselected
     * - `subnav` – A sub-array of subnav items
     *
     * Subnav arrays should be associative, with identifiable keys set to sub-arrays with the following keys:
     *
     * - `label` – The human-facing subnav item label
     * - `url` – The URL the subnav item should link to
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
        $craftPro = Craft::$app->getEdition() === Craft::Pro;
        $isAdmin = Craft::$app->getUser()->getIsAdmin();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $navItems = [
            [
                'label' => Craft::t('app', 'Dashboard'),
                'url' => 'dashboard',
                'fontIcon' => 'gauge',
            ],
        ];

        if (Craft::$app->getSections()->getTotalEditableSections()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Entries'),
                'url' => 'entries',
                'fontIcon' => 'section',
            ];
        }

        if (!empty(Craft::$app->getGlobals()->getEditableSets())) {
            $navItems[] = [
                'label' => Craft::t('app', 'Globals'),
                'url' => 'globals',
                'fontIcon' => 'globe',
            ];
        }

        if (Craft::$app->getCategories()->getEditableGroupIds()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Categories'),
                'url' => 'categories',
                'fontIcon' => 'tree',
            ];
        }

        if (Craft::$app->getVolumes()->getTotalViewableVolumes()) {
            $navItems[] = [
                'label' => Craft::t('app', 'Assets'),
                'url' => 'assets',
                'fontIcon' => 'assets',
            ];
        }

        if ($craftPro && Craft::$app->getUser()->checkPermission('editUsers')) {
            $navItems[] = [
                'label' => Craft::t('app', 'Users'),
                'url' => 'users',
                'fontIcon' => 'users',
            ];
        }

        // Add any Plugin nav items
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        foreach ($plugins as $plugin) {
            if (
                $plugin->hasCpSection &&
                Craft::$app->getUser()->checkPermission('accessPlugin-' . $plugin->id) &&
                ($pluginNavItem = $plugin->getCpNavItem()) !== null
            ) {
                $navItems[] = $pluginNavItem;
            }
        }

        if ($isAdmin) {
            if ($craftPro && $generalConfig->enableGql) {
                $subNavItems = [];

                if ($generalConfig->allowAdminChanges) {
                    $subNavItems['schemas'] = [
                        'label' => Craft::t('app', 'Schemas'),
                        'url' => 'graphql/schemas',
                    ];
                }

                $subNavItems['tokens'] = [
                    'label' => Craft::t('app', 'Tokens'),
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
                    'icon' => '@appicons/graphql.svg',
                    'subnav' => $subNavItems,
                ];
            }
        }

        $utilities = Craft::$app->getUtilities()->getAuthorizedUtilityTypes();

        if (!empty($utilities)) {
            $badgeCount = 0;

            foreach ($utilities as $class) {
                /** @var UtilityInterface $class */
                $badgeCount += $class::badgeCount();
            }

            $navItems[] = [
                'url' => 'utilities',
                'label' => Craft::t('app', 'Utilities'),
                'fontIcon' => 'tool',
                'badgeCount' => $badgeCount,
            ];
        }

        if ($isAdmin) {
            if ($generalConfig->allowAdminChanges) {
                $navItems[] = [
                    'url' => 'settings',
                    'label' => Craft::t('app', 'Settings'),
                    'fontIcon' => 'settings',
                ];
            }

            $navItems[] = [
                'url' => 'plugin-store',
                'label' => Craft::t('app', 'Plugin Store'),
                'fontIcon' => 'plugin',
            ];
        }

        // Allow plugins to modify the nav
        $event = new RegisterCpNavItemsEvent([
            'navItems' => $navItems,
        ]);
        $this->trigger(self::EVENT_REGISTER_CP_NAV_ITEMS, $event);
        $navItems = $event->navItems;

        // Figure out which item is selected, and normalize the items
        $path = Craft::$app->getRequest()->getPathInfo();

        if ($path === 'myaccount') {
            $path = 'users';
        }

        $foundSelectedItem = false;

        foreach ($navItems as &$item) {
            if (!$foundSelectedItem && ($item['url'] == $path || str_starts_with($path, $item['url'] . '/'))) {
                $item['sel'] = true;
                if (!isset($item['subnav'])) {
                    $item['subnav'] = false;
                }
                $foundSelectedItem = true;
            } else {
                $item['sel'] = false;
                $item['subnav'] = false;
            }

            if (!isset($item['id'])) {
                $item['id'] = 'nav-' . preg_replace('/[^\w\-_]/', '', $item['url']);
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
     * Returns the list of settings.
     *
     * @return array
     */
    public function settings(): array
    {
        $settings = [];

        $label = Craft::t('app', 'System');

        $settings[$label]['general'] = [
            'iconMask' => '@appicons/sliders.svg',
            'label' => Craft::t('app', 'General'),
        ];
        $settings[$label]['sites'] = [
            'iconMask' => '@appicons/world.svg',
            'label' => Craft::t('app', 'Sites'),
        ];

        if (!Craft::$app->getConfig()->getGeneral()->headlessMode) {
            $settings[$label]['routes'] = [
                'iconMask' => '@appicons/routes.svg',
                'label' => Craft::t('app', 'Routes'),
            ];
        }

        $settings[$label]['users'] = [
            'iconMask' => '@appicons/users.svg',
            'label' => Craft::t('app', 'Users'),
        ];
        $settings[$label]['email'] = [
            'iconMask' => '@appicons/envelope.svg',
            'label' => Craft::t('app', 'Email'),
        ];
        $settings[$label]['plugins'] = [
            'iconMask' => '@appicons/plugin.svg',
            'label' => Craft::t('app', 'Plugins'),
        ];

        $label = Craft::t('app', 'Content');

        $settings[$label]['fields'] = [
            'iconMask' => '@appicons/field.svg',
            'label' => Craft::t('app', 'Fields'),
        ];
        $settings[$label]['sections'] = [
            'iconMask' => '@appicons/newspaper.svg',
            'label' => Craft::t('app', 'Sections'),
        ];
        $settings[$label]['globals'] = [
            'iconMask' => '@appicons/globe.svg',
            'label' => Craft::t('app', 'Globals'),
        ];
        $settings[$label]['categories'] = [
            'iconMask' => '@appicons/tree.svg',
            'label' => Craft::t('app', 'Categories'),
        ];
        $settings[$label]['tags'] = [
            'iconMask' => '@appicons/tags.svg',
            'label' => Craft::t('app', 'Tags'),
        ];

        $label = Craft::t('app', 'Media');

        $settings[$label]['filesystems'] = [
            'iconMask' => '@appicons/folder-open.svg',
            'label' => Craft::t('app', 'Filesystems'),
        ];

        $settings[$label]['assets'] = [
            'iconMask' => '@appicons/photo.svg',
            'label' => Craft::t('app', 'Assets'),
        ];

        $label = Craft::t('app', 'Plugins');

        $pluginsService = Craft::$app->getPlugins();

        foreach ($pluginsService->getAllPlugins() as $plugin) {
            if ($plugin->hasCpSettings) {
                $settings[$label][$plugin->id] = [
                    'url' => 'settings/plugins/' . $plugin->id,
                    'icon' => $pluginsService->getPluginIconSvg($plugin->id),
                    'label' => $plugin->name,
                ];
            }
        }

        // Allow plugins to modify the settings
        $event = new RegisterCpSettingsEvent([
            'settings' => $settings,
        ]);
        $this->trigger(self::EVENT_REGISTER_CP_SETTINGS, $event);

        return $event->settings;
    }

    /**
     * Returns whether the control panel alerts are cached.
     *
     * @return bool
     */
    public function areAlertsCached(): bool
    {
        // The license key status gets cached on each Craftnet request
        return (Craft::$app->getCache()->get('licenseInfo') !== false);
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
     * Returns the available environment variable and alias suggestions for
     * inputs that support them.
     *
     * @param bool $includeAliases Whether aliases should be included in the list
     * (only enable this if the setting defines a URL or file path)
     * @param callable|null $filter A function that returns whether a given value should be included
     * @phpstan-param callable(scalar):bool|null $filter
     * @return array[]
     * @phpstan-return array{label:string,data:array}[]
     * @since 3.1.0
     */
    public function getEnvSuggestions(bool $includeAliases = false, ?callable $filter = null): array
    {
        $suggestions = [];
        $security = Craft::$app->getSecurity();

        $envSuggestions = [];
        foreach (array_keys($_SERVER) as $var) {
            if (
                is_string($var) &&
                !str_starts_with($var, 'HTTP_') &&
                is_scalar($env = App::env($var)) &&
                (!$filter || $filter($env))
            ) {
                $envSuggestions[] = [
                    'name' => '$' . $var,
                    'hint' => $security->redactIfSensitive($var, Craft::getAlias((string)$env, false)),
                ];
            }
        }
        ArrayHelper::multisort($envSuggestions, 'name');
        $suggestions[] = [
            'label' => Craft::t('app', 'Environment Variables'),
            'data' => $envSuggestions,
        ];

        if ($includeAliases) {
            $aliasSuggestions = [];
            foreach (Craft::$aliases as $alias => $path) {
                if (is_array($path)) {
                    if (
                        isset($path[$alias]) &&
                        (!$filter || $filter($path[$alias]))
                    ) {
                        $aliasSuggestions[] = [
                            'name' => $alias,
                            'hint' => $path[$alias],
                        ];
                    }
                } elseif (!$filter || $filter($path)) {
                    $aliasSuggestions[] = [
                        'name' => $alias,
                        'hint' => $path,
                    ];
                }
            }
            ArrayHelper::multisort($aliasSuggestions, 'name');
            $suggestions[] = [
                'label' => Craft::t('app', 'Aliases'),
                'data' => $aliasSuggestions,
            ];
        }

        return $suggestions;
    }

    /**
     * Returns environment variable options for a select input.
     *
     * @param array|null $allowedValues
     * @return array
     * @since 3.7.22
     */
    public function getEnvOptions(?array $allowedValues = null): array
    {
        if ($allowedValues !== null) {
            if (empty($allowedValues)) {
                return [];
            }

            $allowedValues = array_flip(array_filter($allowedValues));
        }

        $options = [];
        $security = Craft::$app->getSecurity();

        foreach (array_keys($_SERVER) as $var) {
            if (
                is_string($var) &&
                !StringHelper::startsWith($var, 'HTTP_') &&
                is_string($value = App::env($var)) &&
                ($allowedValues === null || isset($allowedValues[$value]))
            ) {
                $data = [];
                if ($value !== '') {
                    $data['hint'] = $security->redactIfSensitive($var, Craft::getAlias($value, false));
                }

                $options[] = [
                    'label' => "$$var",
                    'value' => "$$var",
                    'data' => [
                        'data' => !empty($data) ? $data : false,
                    ],
                ];
            }
        }

        return $this->_envOptions($options);
    }

    /**
     * Returns environment variable options for a boolean menu.
     *
     * @return array
     * @since 3.7.22
     */
    public function getBooleanEnvOptions(): array
    {
        $options = [];

        foreach (array_keys($_SERVER) as $var) {
            if (!is_string($var)) {
                continue;
            }
            $value = App::env($var);
            if ($value === null || $value === '') {
                continue;
            }
            $booleanValue = is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($booleanValue !== null) {
                $options[] = [
                    'label' => "$$var",
                    'value' => "$$var",
                    'data' => [
                        'data' => [
                            'boolean' => $booleanValue,
                        ],
                    ],
                ];
            }
        }

        return $this->_envOptions($options);
    }

    /**
     * @param array $options
     * @return array
     */
    private function _envOptions(array $options): array
    {
        if (!empty($options)) {
            ArrayHelper::multisort($options, 'value');
            array_unshift($options, [
                'optgroup' => Craft::t('app', 'Environment Variables'),
            ]);
        }

        return $options;
    }

    /**
     * Returns all known time zones for a time zone input.
     *
     * @return array
     * @since 3.7.0
     */
    public function getTimeZoneOptions(): array
    {
        // Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
        $options = [];

        $utc = new DateTime();
        $offsets = [];
        $timezoneIds = [];

        foreach (DateTimeZone::listIdentifiers() as $timezoneId) {
            $timezone = new DateTimeZone($timezoneId);
            $transition = $timezone->getTransitions($utc->getTimestamp(), $utc->getTimestamp());
            $abbr = $transition[0]['abbr'];

            $offset = round($timezone->getOffset($utc) / 60);

            if ($offset) {
                $hour = floor($offset / 60);
                $minutes = floor(abs($offset) % 60);
                $format = sprintf("%+03d:%02u", $hour, $minutes);
            } else {
                $format = '';
            }

            $label = "(GMT$format)";
            if (preg_match('/^[A-Z]+$/', $abbr)) {
                $label .= " $abbr";
            }

            $data = [];

            if ($timezoneId !== 'UTC') {
                [, $city] = explode('/', $timezoneId, 2);
                // Cleanup, e.g. North_Dakota/New_Salem => New Salem, North Dakota
                $data['hint'] = str_replace('_', ' ', implode(', ', array_reverse(explode('/', $city))));
            }

            $offsets[] = $offset;
            $timezoneIds[] = $timezoneId;
            $options[] = [
                'value' => $timezoneId,
                'label' => $label,
                'data' => [
                    'data' => !empty($data) ? $data : false,
                ],
            ];
        }

        array_multisort($offsets, SORT_ASC, SORT_NUMERIC, $timezoneIds, $options);

        return $options;
    }

    /**
     * Returns all options for a filesystem input.
     *
     * @return array
     * @since 4.0.0
     */
    public function getFsOptions(): array
    {
        $options = array_map(fn(FsInterface $fs) => [
            'label' => $fs->name,
            'value' => $fs->handle,
        ], Craft::$app->getFs()->getAllFilesystems());

        ArrayHelper::multisort($options, 'label');

        return $options;
    }

    /**
     * Returns all options for a volume input.
     *
     * @return array
     * @since 4.0.0
     */
    public function getVolumeOptions(): array
    {
        $options = array_map(fn(Volume $volume) => [
            'label' => $volume->name,
            'value' => $volume->id,
        ], Craft::$app->getVolumes()->getAllVolumes());

        ArrayHelper::multisort($options, 'label');

        return $options;
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
        if ($language === Craft::$app->language) {
            return null;
        }

        return StringHelper::asciiCharMap(true, $language);
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
        $roots = ArrayHelper::merge([
            '' => [Craft::$app->getPath()->getSiteTemplatesPath()],
        ], Craft::$app->getView()->getSiteTemplateRoots());

        $suggestions = [];
        $templates = [];
        $sites = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $sites[$site->handle] = Craft::t('site', $site->getName());
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

        ArrayHelper::multisort($suggestions, 'name');

        return [
            [
                'label' => Craft::t('app', 'Templates'),
                'data' => $suggestions,
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
        $event = new FormActionsEvent([
            'formActions' => $formActions ?? [],
        ]);
        $this->trigger(self::EVENT_REGISTER_FORM_ACTIONS, $event);
        return $event->formActions ?: null;
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
     */
    public function fieldLayoutDesigner(FieldLayout $fieldLayout, array $config = []): string
    {
        return CpHelper::fieldLayoutDesignerHtml($fieldLayout, $config);
    }
}
