<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Facades\I18N;
use InvalidArgumentException;
use RuntimeException;

/**
 * Settings for the control panel.
 *
 * @since 6.0.0
 */
class CpConfig extends BaseConfig
{
    /**
     * @var array<string, bool|int|string> The default user accessibility preferences that should be applied to users that haven’t saved their preferences yet.
     *
     * The array can contain the following keys:
     *
     * - `useShapes` – Whether shapes should be used to represent statuses.
     * - `underlineLinks` – Whether links should be underlined.
     * - `disableAutofocus` – Whether inputs should make use of the `autofocus` attribute.
     * - `notificationDuration` – How long notifications should be shown before they disappear automatically (in
     *   milliseconds). Set to `0` to show them indefinitely.
     * - `notificationPosition` – Where notifications should be shown on the screen (`'start-start'` for top-left,
     *   `'start-end'` for top-right, `'end-start'` for bottom-left, or `'end-end'` for bottom-right, when using an
     *   LTR orientation).
     * - `slideoutPosition` – Where slideouts should be shown on the screen (`'start'` for left, or `'end'`
     *   for right, when using an LTR orientation).
     */
    public array $accessibilityDefaults = [
        'useShapes' => false,
        'underlineLinks' => false,
        'disableAutofocus' => false,
        'notificationDuration' => 5000,
        'notificationPosition' => 'end-start',
        'slideoutPosition' => 'end',
    ];

    /**
     * The default language the control panel should use for users who haven’t set a preferred language yet.
     */
    public ?string $defaultLanguage = null;

    /**
     * The default locale the control panel should use for date/number formatting, for users who haven’t set a
     * preferred language or formatting locale.
     *
     * If this is `null`, the `defaultLanguage` setting will determine which locale is used for date/number formatting.
     */
    public ?string $defaultLocale = null;

    /**
     * @var string[] The IDs of utilities that should be disabled.
     */
    public array $disabledUtilities = [];

    /**
     * @var list<array{0: string, 1?: array<string, mixed>}> Additional HTML tags that should be included in the `<head>` of control panel pages.
     *
     * Each tag can be specified as an array of the tag name and its attributes, such as a custom favicon:
     *
     * ```php
     * ->headTags([
     *     ['link', ['rel' => 'icon', 'href' => '/icons/favicon.ico']],
     *     ['link', ['rel' => 'icon', 'type' => 'image/svg+xml', 'sizes' => 'any', 'href' => '/icons/favicon.svg']],
     * ])
     * ```
     */
    public array $headTags = [];

    /**
     * The server path to the control panel’s icon, shown beside the system name in the global sidebar.
     *
     * This can be set to an aliased path such as `@root/branding/icon.svg`.
     *
     * A square SVG file is recommended. It will be displayed at 32px by 32px.
     *
     * SVG files are rendered inline, so they can adapt to the control panel’s light and dark themes. The selected
     * theme may differ from the operating system’s, so use the [`light-dark()`](https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/light-dark)
     * CSS function or `currentColor` within the SVG, rather than a `prefers-color-scheme` media query.
     */
    public ?string $iconPath = null;

    /**
     * The server path to the logo shown on the login page and other sign-in screens.
     *
     * This can be set to an aliased path such as `@root/branding/logo.svg`.
     *
     * An SVG file is recommended. It will be displayed at 288px wide.
     *
     * SVG files are rendered inline, so they can adapt to the control panel’s light and dark themes. Use the
     * `light-dark()` CSS function or `currentColor` within the SVG, as you would for `iconPath`. Once nobody is
     * logged in, sign-in screens show the theme the last user to sign in on the browser had selected.
     */
    public ?string $logoPath = null;

    /**
     * The URL to a CSS file that should be included when rendering system templates on the front end, such as the
     * Login and Set Password templates.
     */
    public ?string $systemTemplateCss = null;

    /** @param  array<string, bool|int|string>  $value */
    public function accessibilityDefaults(array $value): self
    {
        $this->accessibilityDefaults = $value;

        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function defaultLanguage(?string $value): self
    {
        if ($value !== null) {
            try {
                $value = I18N::normalizeLanguage($value);
                /** @phpstan-ignore catch.neverThrown */
            } catch (InvalidArgumentException $e) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }
        }

        $this->defaultLanguage = $value;

        return $this;
    }

    public function defaultLocale(?string $value): self
    {
        $this->defaultLocale = $value;

        return $this;
    }

    /** @param  string[]  $value */
    public function disabledUtilities(array $value): self
    {
        $this->disabledUtilities = $value;

        return $this;
    }

    /** @param  list<array{0: string, 1?: array<string, mixed>}>  $value */
    public function headTags(array $value): self
    {
        $this->headTags = $value;

        return $this;
    }

    public function iconPath(?string $value): self
    {
        $this->iconPath = $value;

        return $this;
    }

    public function logoPath(?string $value): self
    {
        $this->logoPath = $value;

        return $this;
    }

    public function systemTemplateCss(?string $value): self
    {
        $this->systemTemplateCss = $value;

        return $this;
    }
}
