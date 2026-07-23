# HTML Sanitizers

Craft 6 includes a Laravel-native `HtmlSanitizerManager` built on [Symfony HtmlSanitizer](https://symfony.com/doc/current/html_sanitizer.html). Application code can access it through the plural `HtmlSanitizers` facade.

The manager lets plugins and apps:

- sanitize HTML with Craft’s default sanitizer
- register named sanitizers for project-specific rules
- customize the default sanitizer config
- reuse Craft’s default config when defining custom sanitizers

Legacy HtmlPurifier support remains available through the Yii2 adapter layer. The legacy Twig `|purify` filter still uses HtmlPurifier, while `|sanitize` uses the sanitizer manager.

## Basic Usage

```php
<?php

use CraftCms\Cms\Support\Facades\HtmlSanitizers;

$cleanHtml = HtmlSanitizers::sanitize($dirtyHtml);
```

Craft’s default sanitizer starts from Symfony’s safe and static element sets, then adds support for relative links and media URLs, `div[data-oembed-url]`, `oembed[url]`, and Craft’s video embed URL sanitizer.

## Registering Named Sanitizers

Register extensions from a service provider’s `boot()` method:

```php
<?php

namespace App\Providers;

use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        HtmlSanitizers::extend('links-only', new HtmlSanitizer(
            (new HtmlSanitizerConfig())
                ->allowElement('a')
                ->allowAttribute('href', ['a'])
        ));
    }
}
```

`extend()` accepts any of these definitions:

- an array of sanitizer settings
- an `HtmlSanitizerInterface` instance
- a creator closure that returns either an array or an `HtmlSanitizerInterface` instance

Laravel binds anonymous creator closures to the manager and passes the service container as their first argument.

```php
HtmlSanitizers::extend('links-only', fn () => [
    'allow_elements' => [
        'a' => ['href'],
    ],
]);
```

Use a sanitizer by name or resolve it directly:

```php
$cleanHtml = HtmlSanitizers::sanitize($dirtyHtml, 'links-only');
$cleanHtml = HtmlSanitizers::sanitizer('links-only')->sanitize($dirtyHtml);
```

## Array Config Files

Named sanitizers can also be defined in `config/craft/sanitizers/`. The file name becomes the sanitizer name and the returned array is loaded through Laravel’s configuration repository, including when configuration is cached.

```php
<?php

// config/craft/sanitizers/no-headings.php
return [
    'allow_safe_elements' => true,
    'block_elements' => ['h1'],
];
```

Config files must return arrays; closures and object instances are not compatible with `config:cache`. Unknown definition keys are ignored.

```twig
{{ body|sanitize('no-headings') }}
```

A `default.php` definition replaces Craft’s built-in default sanitizer.

## Customizing the Default Sanitizer

Use `defaults()` to transform Craft’s default `HtmlSanitizerConfig`. Every callback must return an `HtmlSanitizerConfig`.

```php
HtmlSanitizers::defaults(static fn (HtmlSanitizerConfig $config) => $config
    ->allowAttribute('class', ['p', 'span'])
    ->allowElement('iframe')
    ->allowAttribute('src', ['iframe'])
);
```

To create a named sanitizer starting from Craft’s defaults:

```php
HtmlSanitizers::extend('embedded-content', fn () => new HtmlSanitizer(
    HtmlSanitizers::defaultConfig()
        ->allowElement('iframe')
        ->allowAttribute('src', ['iframe'])
));
```

## Resolution and Caching

Sanitizers are created lazily and cached by name. Register extensions and default callbacks before their sanitizers are first resolved. Replacing a creator or adding a default callback does not change an already-resolved sanitizer. Call `HtmlSanitizers::forgetDrivers()` to clear all resolved instances when an intentional runtime change must take effect.

Calling `all()` eagerly resolves every registered sanitizer. `getDrivers()` returns only sanitizers that have already been resolved.

## Twig Filter

```twig
{{ body|sanitize }}
{{ body|sanitize('links-only') }}
```

You may also pass a concrete sanitizer instance from PHP into a template context and use it directly with `|sanitize`.
