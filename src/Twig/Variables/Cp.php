<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Cp\Alerts;
use CraftCms\Cms\Cp\Events\FormActionsResolving;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
use DateTimeInterface;
use Deprecated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Uri;
use InvalidArgumentException;
use Stringable;

use function CraftCms\Cms\t;

class Cp extends Component
{
    /**
     * Returns the site the control panel is currently working with, via a `site` query string param if sent.
     *
     * @return Site|null The site, or `null` if the user doesn’t have permission to edit any sites.
     */
    public function getRequestedSite(): ?Site
    {
        return app(RequestedSite::class)->get();
    }

    /**
     * Returns the Craft Console account URL.
     */
    public function craftIdAccountUrl(): string
    {
        return Api::craftIdEndpoint().'/account';
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
     */
    public function nav(): array
    {
        return app(Navigation::class)->getItems();
    }

    /**
     * Returns whether the control panel alerts are cached.
     */
    public function areAlertsCached(): bool
    {
        // The license key status gets cached on each Craftnet request
        return ! is_null(Cache::get(License::CACHE_KEY_LICENSE_INFO));
    }

    /**
     * Returns an array of alerts to display in the control panel.
     */
    public function getAlerts(): array
    {
        return app(Alerts::class)->get(request()->craftPath());
    }

    /**
     * Returns info about the active trials.
     *
     * @internal
     */
    public function trialInfo(): ?array
    {
        $issues = collect(app(License::class)->issues([
            LicenseKeyStatus::Trial->value,
            LicenseKeyStatus::Astray->value,
            'wrong_edition',
        ]));

        if ($issues->isEmpty()) {
            return null;
        }

        $cmsIssues = $issues->filter(fn ($issue) => in_array($issue[2]['type'], ['cms-edition', 'cms-renewal']));
        $pluginEditionIssues = $issues->filter(fn ($issue) => $issue[2]['type'] === 'plugin-edition');
        $pluginRenewalIssues = $issues->filter(fn ($issue) => $issue[2]['type'] === 'plugin-renewal');

        $names = $cmsIssues->map(fn ($issue) => $issue[0])->all();
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
        $cartUrl = Uri::of("$consoleUrl/cart/new")
            ->withQuery(['items' => $issues->map(fn ($issue) => $issue[2])->all()])
            ->value();

        return [
            'message' => $message,
            'cartUrl' => $cartUrl,
        ];
    }

    /**
     * Returns the available environment variable and alias suggestions for
     * inputs that support them.
     *
     * @param  bool  $includeAliases  Whether aliases should be included in the list
     *                                (only enable this if the setting defines a URL or file path)
     * @param  callable|null  $filter  A function that returns whether a given value should be included
     *
     * @phpstan-param callable(scalar):bool|null $filter
     *
     * @return array[]
     *
     * @phpstan-return array{label:string,data:array}[]
     */
    #[Deprecated(message: 'in 6.0.0.  [[\CraftCms\Cms\Cp\SelectOptions::getEnvSuggestions]] should be used instead.')]
    public function getEnvSuggestions(bool $includeAliases = false, ?callable $filter = null): array
    {
        return $this->formatLegacySuggestions(SelectOptions::getEnvSuggestions($includeAliases, $filter));
    }

    /**
     * Returns environment variable options for a select input.
     */
    #[Deprecated(message: 'in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getEnvOptions] should be used instead.')]
    public function getEnvOptions(?array $allowedValues = null): array
    {
        return $this->formatLegacyOptions(SelectOptions::getEnvOptions($allowedValues));
    }

    /**
     * Returns environment variable options for a boolean menu.
     */
    #[Deprecated(message: 'in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getBooleanEnvOptions] should be used instead.')]
    public function getBooleanEnvOptions(): array
    {
        return $this->formatLegacyOptions(SelectOptions::getBooleanEnvOptions());
    }

    /**
     * Returns environment variable options for a language menu.
     *
     * @param  bool  $appOnly  Whether to limit the env options to those that match available app locales
     */
    #[Deprecated(message: 'in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getLanguageEnvOptions]] shoudl be used instead.')]
    public function getLanguageEnvOptions(bool $appOnly = false): array
    {
        return $this->formatLegacyOptions(SelectOptions::getLanguageEnvOptions($appOnly));
    }

    /**
     * Returns all known time zones for a time zone input.
     *
     * @param  DateTimeInterface|null  $offsetDate  The [[DateTime]] object that contains the date/time to compute time zone offsets from
     */
    #[Deprecated(message: 'in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getTimezoneOptions]] should be used instead.')]
    public function getTimeZoneOptions(?DateTimeInterface $offsetDate = null): array
    {
        return $this->formatLegacyOptions(SelectOptions::getTimeZoneOptions($offsetDate));
    }

    /**
     * Returns all known language options for a language input.
     *
     * @param  bool  $showLocaleIds  Whether to show the hint as locale id; e.g. en, en-GB
     * @param  bool  $showLocalizedNames  Whether to show the hint as localizes names; e.g. English, English (United Kingdom)
     * @param  bool  $appLocales  Whether to limit the returned locales to just app locales (cp translation options) or show them all
     */
    #[Deprecated(message: 'in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getLanguageOptions]] should be used instead.')]
    public function getLanguageOptions(
        bool $showLocaleIds = false,
        bool $showLocalizedNames = false,
        bool $appLocales = false,
    ): array {
        return array_map(fn ($locale) => [
            'label' => $locale['label'],
            'value' => $locale['value'],
            'data' => [
                'data' => array_filter([
                    'keywords' => $locale['data']['keywords'] ?? null,
                    'hintLang' => $locale['data']['lang'] ?? null,
                    'hint' => $locale['data']['hint'] ?? null,
                ]),
            ],
        ], SelectOptions::getLanguageOptions($showLocaleIds, $showLocalizedNames, $appLocales));
    }

    /**
     * Returns all options for a filesystem input.
     */
    #[Deprecated(message: 'in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getFsOptions]] should be used instead.')]
    public function getFsOptions(): array
    {
        return SelectOptions::getFsOptions();
    }

    /**
     * Returns all options for a volume input.
     */
    #[Deprecated(message: 'in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getVolumeOptions]] should be used instead.')]
    public function getVolumeOptions(): array
    {
        return SelectOptions::getVolumeOptions();
    }

    /**
     * Returns ASCII character mappings for the given language, if it differs from the application language.
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
     *
     * @phpstan-return array{label:string,data:array}[]
     */
    #[Deprecated(message: 'in 6.0.0. [[\CraftCms\Cms\Cp\SelectOptions::getTemplateSuggestions]] should be used instead.')]
    public function getTemplateSuggestions(): array
    {
        $suggestions = SelectOptions::getTemplateSuggestions();

        return $this->formatLegacySuggestions($suggestions);
    }

    public function prepFormActions(?array $formActions): ?array
    {
        event($event = new FormActionsResolving($formActions ?? []));

        return $event->formActions;
    }

    /**
     * Renders a field’s HTML, for the given input HTML or a template.
     *
     * @param  string  $input  The input HTML or template path. If passing a template path, it must begin with `template:`.
     *
     * @throws TemplateLoaderException if $input begins with `template:` and is followed by an invalid template path
     * @throws InvalidArgumentException if `$config['siteId']` is invalid
     */
    public function field(string|Stringable $input, array $config = []): string
    {
        return FormFields::fieldHtml($input, $config);
    }

    /**
     * Renders a text input's HTML from the legacy text variables.
     */
    public function text(array $config = []): string
    {
        return FormFields::textFromConfig($config)->toHtml();
    }

    /**
     * Renders a textarea's HTML from the legacy textarea variables.
     */
    public function textarea(array $config = []): string
    {
        return FormFields::textareaFromConfig($config)->toHtml();
    }

    /**
     * Renders a password input's HTML from the legacy password variables.
     */
    public function password(array $config = []): string
    {
        return FormFields::passwordFromConfig($config)->toHtml();
    }

    /**
     * Renders a lightswitch's HTML from the legacy lightswitch variables.
     */
    public function lightswitch(array $config = []): string
    {
        return FormFields::lightswitchFromConfig($config)->toHtml();
    }

    /**
     * Renders a button's HTML from the legacy button variables.
     */
    public function button(array $config = []): string
    {
        return FormFields::buttonFromConfig($config)->toHtml();
    }

    /**
     * Renders a button group's HTML from the legacy buttonGroup variables.
     */
    public function buttonGroup(array $config = []): string
    {
        return FormFields::buttonGroupFromConfig($config)->toHtml();
    }

    /**
     * Renders a checkbox's HTML from the legacy checkbox variables.
     */
    public function checkbox(array $config = []): string
    {
        return FormFields::checkboxFromConfig($config)->toHtml();
    }

    /**
     * Renders a checkbox group's HTML from the legacy checkboxGroup variables.
     */
    public function checkboxGroup(array $config = []): string
    {
        return FormFields::checkboxGroupFromConfig($config)->toHtml();
    }

    /**
     * Renders a checkbox select's HTML from the legacy checkboxSelect variables.
     */
    public function checkboxSelect(array $config = []): string
    {
        return FormFields::checkboxSelectFromConfig($config)->toHtml();
    }

    /**
     * Renders a radio's HTML from the legacy radio variables.
     */
    public function radio(array $config = []): string
    {
        return FormFields::radioFromConfig($config)->toHtml();
    }

    /**
     * Renders a radio group's HTML from the legacy radioGroup variables.
     */
    public function radioGroup(array $config = []): string
    {
        return FormFields::radioGroupFromConfig($config)->toHtml();
    }

    private function formatLegacySuggestions(array $options): array
    {
        return array_map(fn ($group) => [
            'label' => $group['label'],
            'data' => array_map(fn (array $option) => [
                'name' => $option['label'],
                'hint' => $option['data']['hint'] ?? null,
            ], $group['options']),
        ], $options);
    }

    private function formatLegacyOptions(array $originalOptions): array
    {
        $options = [];

        foreach ($originalOptions as $value) {
            if (($value['type'] ?? null) === 'optgroup') {
                $options[] = ['optgroup' => $value['label']];
                array_push($options, ...($value['options'] ?? []));
            } else {
                $options[] = [
                    'label' => $value['label'],
                    'value' => $value['value'],
                    'data' => $value['data'] ?? null,
                ];
            }
        }

        return $options;
    }
}
