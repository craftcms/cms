# Configuration Overview

There are several ways to configure Craft depending on your needs.

[[toc]]

## General Config Settings

Craft supports several [general config settings](config-settings.md). You can override their default values in your `config/general.php` file.

```php
return [
    'devMode' => true,
];
```

## Database Connection Settings

Craft supports several [database connection settings](db-settings.md). You can override their default values in your `config/db.php` file.

## Guzzle Config

Craft uses [Guzzle 6](http://docs.guzzlephp.org/en/latest/) whenever creating HTTP requests, such as:

- when checking for Craft updates
- when sending in a support request from the Craft Support widget
- when loading RSS feeds from the Feeds widget
- when working with assets on remote volumes, like Amazon S3

You can customize the config settings Guzzle uses when sending these requests by creating a `guzzle.php` file in your `config/` folder. The file should return an array, with your config overrides.

```php
<?php

return [
    'headers' => ['Foo' => 'Bar'],
    'query'   => ['testing' => '123'],
    'auth'    => ['username', 'password'],
    'proxy'   => 'tcp://localhost:80',
];
```

The options defined here will be passed into new `GuzzleHttp\Client` instances. See [Guzzle’s documentation](http://docs.guzzlephp.org/en/latest/) for a list of available options.

## Aliases

Some settings and functions in Craft support [Yii aliases](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases), which are basically placeholders for base file system paths and URLs. These include:

- Sites’ Base URL settings
- Volumes’ Base URL settings
- Local volumes’ File System Path settings
- The <config:resourceBasePath> and <config:resourceBaseUrl> config settings
- The [svg()](../dev/functions.md#svg-svg-sanitize) Twig function

The following aliases are available out of the box:

| Alias | Description
| ----- | -----------
| `@app` | The path to `vendor/craftcms/cms/src/`
| `@config` | The path to your `config/` folder
| `@contentMigrations` | The path to your `migrations/` folder
| `@craft` | The path to `vendor/craftcms/cms/src/`
| `@lib` | The path to `vendor/craftcms/cms/lib/`
| `@root` | The root project path (same as the [CRAFT_BASE_PATH](php-constants.md#craft-base-path) PHP constant)
| `@runtime` | The path to your `storage/runtime/` folder
| `@storage` | The path to your `storage/` folder
| `@templates` | The path to your `templates/` folder
| `@translations` | The path to your `translations/` folder
| `@vendor` | The path to your `vendor/` folder
| `@web` | The URL to the folder that contains the `index.php` file that was loaded for the request
| `@webroot` | The path to the folder that contains the `index.php` file that was loaded for the request

You can define additional custom aliases using the <config:aliases> config setting. For example, you may wish to create aliases that define the base URL and base path that your asset volumes will live in.

```php
'aliases' => [
    '@assetBaseUrl' => 'http://my-project.com/assets',
    '@assetBasePath' => '/path/to/web/assets',
],
```

With those in place, you could begin your asset volumes’ Base URL and File System Path settings with them, e.g. `@assetBaseUrl/user-photos` and `@assetBasePath/user-photos`.

If you’d like, you can set the alias values with environment variables, either from your `.env` file or somewhere in your environment’s configuration:

```bash
ASSETS_BASE_URL=http://my-project.com/assets
ASSETS_BASE_PATH=/path/to/web/assets
```

Then you can pull them into the alias definitions using [getenv()](http://php.net/manual/en/function.getenv.php):

```php
'aliases' => [
    '@assetBaseUrl' => getenv('ASSETS_BASE_URL'),
    '@assetBasePath' => getenv('ASSETS_BASE_PATH'),
],
```

::: tip
When referencing aliases in your settings, you can append additional segments onto the URL or path. For example, you can set a volume’s base URL to `@assetBaseUrl/user-photos`.
:::

::: tip
You can parse aliases in your templates by passing them to the [alias()](../dev/functions.html#alias-string) function:

```twig
{{ alias('@assetBaseUrl') }}
```
:::

## URL Rules

You can define custom [URL rules](https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing#url-rules) in `config/routes.php`. See [Routing](../routing.md) for more details.

## PHP Constants

You can configure core settings like system file paths and the active environment by defining certain [PHP constants](php-constants.md) in `web/index.php`.

## Application Configuration

You can customize Craft’s [application configuration](app.md) from `config/app.php`, such as overriding component configs, or adding new modules and components.
