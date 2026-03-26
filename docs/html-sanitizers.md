# HTML Sanitizers

Craft 6 includes a Laravel-native HTML sanitizer registry built on [Symfony HtmlSanitizer](https://symfony.com/doc/current/html_sanitizer.html).

The `HtmlSanitizers` service lets plugins and apps:

- sanitize HTML with Craft's default sanitizer
- register named sanitizers for project-specific rules
- customize the default sanitizer config
- reuse Craft's default config when defining custom sanitizers

Legacy HtmlPurifier support remains available through the Yii2 adapter layer. The legacy Twig `|purify` filter still uses HtmlPurifier, while the new `|sanitize` filter uses the new sanitizer registry.

## Basic Usage

Use the facade and call `sanitize()`:

```php
<?php

use CraftCms\Cms\Support\Facades\HtmlSanitizers;

$cleanHtml = HtmlSanitizers::sanitize($dirtyHtml);
```

This uses Craft's default sanitizer.

## Default Behavior

Craft's default sanitizer starts from Symfony's safe and static element sets, then adds Craft-specific support for:

- relative links and media URLs
- `div[data-oembed-url]`
- `oembed[url]`
- Craft's video embed URL sanitizer

The default sanitizer is intended for general safe rich text output.

## Registering Named Sanitizers

Plugins and apps can register named sanitizers from a service provider. Registered sanitizers may be concrete `HtmlSanitizerInterface` instances or closures that return one.

```php
<?php

namespace App\Providers;

use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AppServiceProvider extends ServiceProvider
{
    public function boot(HtmlSanitizers $sanitizers): void
    {
        $sanitizers->register('links-only', new HtmlSanitizer(
            (new HtmlSanitizerConfig())
                ->allowElement('a')
                ->allowAttribute('href', ['a'])
        ));
    }
}
```

Use the registered sanitizer by name:

```php
<?php

use CraftCms\Cms\Support\Facades\HtmlSanitizers;

$cleanHtml = HtmlSanitizers::sanitize($dirtyHtml, 'links-only');
```

You can also resolve the sanitizer instance directly:

```php
<?php

use CraftCms\Cms\Support\Facades\HtmlSanitizers;

$cleanHtml = HtmlSanitizers::sanitizer('links-only')->sanitize($dirtyHtml);
```

## Customizing the Default Sanitizer

Use `defaults()` to modify Craft's default `HtmlSanitizerConfig`.

```php
<?php

namespace App\Providers;

use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AppServiceProvider extends ServiceProvider
{
    public function boot(HtmlSanitizers $sanitizers): void
    {
        $sanitizers->defaults(fn (HtmlSanitizerConfig $config) => $config
            ->allowAttribute('class', ['p', 'span'])
            ->allowElement('iframe')
            ->allowAttribute('src', ['iframe'])
        );
    }
}
```

Each callback receives the current default config and may return the same config or a modified one.

The default sanitizer instance is built lazily from `defaultConfig()`, so calling `defaults()` changes what `sanitize($html)` uses by default.

## Reusing the Default Config

If you want a custom named sanitizer that starts from Craft's defaults, call `defaultConfig()` and keep building from there.

```php
<?php

namespace App\Providers;

use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

class AppServiceProvider extends ServiceProvider
{
    public function boot(HtmlSanitizers $sanitizers): void
    {
        $config = $sanitizers->defaultConfig()
            ->allowElement('iframe')
            ->allowAttribute('src', ['iframe']);

        $sanitizers->register('embedded-content', new HtmlSanitizer($config));
    }
}
```

This is the recommended way to define custom sanitizers that should stay close to Craft's defaults.

## Twig Filters

Craft now provides two distinct Twig filters:

- `|sanitize` uses the new `HtmlSanitizers` service

Examples:

```twig
{{ body|sanitize }}
{{ body|sanitize('links-only') }}
```

You may also pass a concrete sanitizer instance from PHP into a template context and use it directly with `|sanitize`.

## Recommended Direction

For new code:

- prefer the `HtmlSanitizers` service or facade for application code
- prefer `|sanitize` in Twig
- define named sanitizers in service providers instead of config files when possible
