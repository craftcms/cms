Configuration
=============

- [PHP Constants](#php-constants)
- [General Config](#general-config)
- [Database Config](#database-config)
- [Data Caching Config](#data-caching-config)
- [Guzzle Config](#guzzle-config)
- [Overriding Volume Settings](#overriding-volume-settings)
- [URL Rules](#url-rules)
- [Redactor Configs](#redactor-configs)
- [Application Config](#application-config)
  - [Mailer Component](#mailer-config)

## PHP Constants

Your `web/index.php` file can specify a few PHP constants, which Craft’s bootstrap script will check for while loading and configuring Craft.

- `CRAFT_VENDOR_PATH` – The path to the `vendor/` directory. (It is assumed to live 4 directories up from the bootstrap script by default.)
- `CRAFT_BASE_PATH` – The path to the **base directory** that Craft will look for `config/`, `templates/`, and other directories within by default. (It is assumed to be the parent of the `vendor/` directory by default.)
- `CRAFT_CONFIG_PATH` – The path to the `config/` directory. (It is assumed to live within the base directory by default.)
- `CRAFT_CONTENT_MIGRATIONS_PATH` – The path to the `migrations/` directory used to store content migrations. (It is assumed to live within the base directory by default.)
- `CRAFT_PLUGINS_PATH` – The path to the `plugins/` directory used to store manually-installed plugins. (It is assumed to live within the base directory by default.)
- `CRAFT_STORAGE_PATH` – The path to the `storage/` directory. (It is assumed to live within the base directory by default.)
- `CRAFT_TEMPLATES_PATH` – The path to the `templates/` directory. (It is assumed to live within the base directory by default.)
- `CRAFT_TRANSLATIONS_PATH` – The path to the `translations/` directory. (It is assumed to live within the base directory by default.)
- `CRAFT_ENVIRONMENT` – The environment ID that multi-environment configs can reference when defining their environment-specific config values. (`$_SERVER['SERVER_NAME']` will be used by default.)
- `CRAFT_SITE` – The Site handle or ID that Craft should be serving from this `index.php` file.

## General Config

Craft supports several general configuration settings. You can see a list of them all in `vendor/craftcms/cms/src/config/defaults/general.php`, and you can override the values in your `config/general.php` file.

## Database Config

Craft supports several database configuration settings. You can see a list of them all in `vendor/craftcms/cms/src/config/defaults/db.php`, and you can override the values in your `config/db.php` file.

## Data Caching Config

If you’re using APC, Database, File, or Memcache(d) data caching drivers (per the `cacheMethod` general config setting), you can set driver-specific settings. You can see a list of them in the corresponding file in `vendor/craftcms/cms/src/config/defaults/` (`apc.php`, `dbcache.php`, `filecache.php`, or `memcache.php`), and you can override the values by creating a file with the same name in your `config/` directory.

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
    'defaults' => [
        'headers' => ['Foo' => 'Bar'],
        'query'   => ['testing' => '123'],
        'auth'    => ['username', 'password'],
        'proxy'   => 'tcp://localhost:80'
    ]
];
```

The options defined here will be passed into new `GuzzleHttp\Client` instances. See [Guzzle’s documentation](http://docs.guzzlephp.org/en/latest/) for a list of available options.

## Overriding Volume Settings

If you would prefer to define volume settings with a config file, you can do that from `config/volumes.php`. The file should return an array whose keys map to your volume handles, and values are nested arrays that define the overridden setting values.

```php
return [
    'siteAssets' => [
        'path' => getenv('ASSETS_BASE_PATH').'/site',
        'url' => getenv('ASSETS_BASE_URL').'/site',
    ],
    'companyLogos' => [
        'path' => getenv('ASSETS_BASE_PATH').'/logos',
        'url' => getenv('ASSETS_BASE_URL').'/logos',
    ],
];
```

## URL Rules

You can define custom [URL rules](http://www.yiiframework.com/doc-2.0/guide-runtime-routing.html#url-rules) in `config/routes.php`, which will get merged in with any routes you’ve defined on the Settings → Routes page in the Control Panel.

Craft supports a custom syntax for routing requests to a template, rather than a controller action:

```php
return [
    'blog/archive/<year:\d{4}>' => ['template' => 'blog/_archive'],
];
```

## Redactor Configs

You can customize the Redactor configurations that are available to Rich Text fields by saving them as `.json` files within your `config/redactor/` directory. The available config settings are listed in [Redactor’s documentation](https://imperavi.com/redactor/docs/settings/).

These `.json` files must contain **valid JSON**. That means:

- No comments
- All object properties (the config setting names) must be wrapped in double quotes
- All strings must use double quotes rather than single quotes

```javascript
// Bad:
{
  /* interesting comment */
  buttons: ['bold', 'italic']
}

// Good:
{
  "buttons": ["bold", "italic"]
}
```

## Application Config

You can customize Craft’s entire [application configuration](http://www.yiiframework.com/doc-2.0/guide-structure-applications.html#application-configurations) from `config/app.php`. Any items returned by that array will get merged into the main application configuration array. 

### Mailer Component

To override the `mailer` component config (which is responsible for sending emails), do this in `config/app.php`:

```php
return [
    'components' => [
        'mailer' => function() {
            // Get the stored email settings
            $settings = Craft::$app->systemSettings->getEmailSettings();

            // Override the transport adapter class
            $settings->transportType = craft\mailgun\MailgunAdapter::class;

            // Override the transport adapter settings
            $settings->transportSettings = [
                'domain' => 'foo.com',
                'apiKey' => 'key-xxxxxxxxxx',
            ];

            return MailerHelper::createMailer($settings);
        },

        // ...
    ],

    // ...
];
```
