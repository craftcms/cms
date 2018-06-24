# PHP Constants

There are a few optional PHP constants that you can define which affect Craft at a lower level than the [config settings](config-settings.md).

If you wish to define any of these constants for your site, you should put them in the `index.php` file in your site’s web root, after setting `$craftPath`:

```php
// Path to your craft/ folder
$craftPath = '../../craft';

// This is for the Dutch site
define('CRAFT_SITE_URL', 'http://example.com/nl/');
define('CRAFT_LOCALE',   'nl');
```

## General Constants

### `CRAFT_ENVIRONMENT`

Defines the name of the current environment, which defaults to the server’s host name. (See [multi-environment-configs](multi-environment-configs.md) for more info.)

```php
define('CRAFT_ENVIRONMENT', 'dev');
```

### `CRAFT_LOCALE`

Sets the locale for the current request. Without setting this, your site’s primary locale will always be used.

```php
define('CRAFT_LOCALE', 'nl');
```

### `CRAFT_SITE_URL`

Overrides the Site URL setting in Settings → General. That can be useful in combination with [CRAFT_LOCALE](#craft-locale) for creating a locale-specific version of your site.

```php
define('CRAFT_SITE_URL', "http://example.com/nl/");
```

You’re free to use [environment-specific variables](multi-environment-configs.md#environment-specific-variables) in the value as well:

```php
define('CRAFT_SITE_URL', "http://{host}/nl");
```

## Path Constants

### `CRAFT_BASE_PATH`

Craft uses this as the starting point for finding [all of the folders](folder-structure.md "Folders located within craft/") traditionally located in `craft/`, with the notable exception of `craft/app/`, whose path gets defined with the help of your `$craftPath` variable in `index.php`.

```php
// Path to your craft/ folder (where the app/ folder lives)
$craftPath = '../../craft';

// Path to where the rest of the craft/* folders live
define('CRAFT_BASE_PATH', '../craft_sitefiles/');
```

### `CRAFT_CONFIG_PATH`

Defines the path to your [craft/config/](folder-structure.md#craft-config) folder.

```php
define('CRAFT_CONFIG_PATH', '../craft_sitefiles/config/');
```

### `CRAFT_PLUGINS_PATH`

Defines the path to your [craft/plugins/](folder-structure.md#craft-plugins) folder.

```php
define('CRAFT_PLUGINS_PATH', '../craft_sitefiles/plugins/');
```

### `CRAFT_STORAGE_PATH`

Defines the path to your [craft/storage/](folder-structure.md#craft-storage) folder.

```php
define('CRAFT_STORAGE_PATH', '../craft_sitefiles/storage/');
```

### `CRAFT_TEMPLATES_PATH`

Defines the path to your [craft/templates/](folder-structure.md#craft-templates) folder.

```php
define('CRAFT_TEMPLATES_PATH', '../craft_sitefiles/templates/');
```

### `CRAFT_TRANSLATIONS_PATH`

Defines the path to your [craft/translations/](folder-structure.md#craft-translations) folder.

```php
define('CRAFT_TRANSLATIONS_PATH', '../craft_sitefiles/translations/');
```
