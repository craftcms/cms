# Multi-Environment Configs

If you want to share the same Craft config files across two or more environments (e.g. Dev, Staging, and Production), you’re in luck! Craft makes it extremely easy to do so.

Normally, your `craft/config/db.php` and `craft/config/general.php` files would return a one-dimensional array of whatever [configuration settings](config-settings.md) you want to override for the given site:

```php
return array(
    'omitScriptNameInUrls' => true,
);
```

Without any help on Craft’s part, you could technically add multi-environment support to that yourself, by either returning different arrays depending on the current server, or using ternary operators within the array:

```php
return array(
    'omitScriptNameInUrls' => true,
    'devMode' => ($_SERVER['SERVER_NAME'] == 'example.test' ? true : false),
);
```

That can get a little cumbersome though. Thankfully, Craft supports the ability for you to return nested, environment-specific arrays.

First, place all of your common config settings into a nested array with the key `'*'`:

```php
return array(
    '*' => array(
        'omitScriptNameInUrls' => true,
    )
);
```

Whatever is listed in the `'*'` array will be applied to all environments.

::: tip
The `'*'` array is required to enable Craft’s multi-environment config support, even if you don’t need it. Craft specifically checks for it when deciding whether to enable multi-environment config support or not.
:::

Next up, add a new nested array for each environment-specific config you need:

```php
return array(
    '*' => array(
        'omitScriptNameInUrls' => true,
    ),

    'example.test' => array(
        'devMode' => true,
    ),

    'example.com' => array(
        'cooldownDuration' => 0,
    )
);
```

Craft will compare the additional array keys (`'example.test'` and `'example.com'`) with the `$_SERVER['SERVER_NAME']` server environment variable. If your server is configured properly, that should be your site’s domain name (without the ‘http://’).

::: tip
You can change the string Craft uses to determine the current environment by defining the [`CRAFT_ENVIRONMENT`](php-constants.md#craft-environment) constant in your `index.php` file.
:::

When Craft is comparing your config keys with `$_SERVER['SERVER_NAME']`, it’s only looking for a *partial match*. So the environment key `'example.com'` will also work if you’re accessing your site via www.example.com or any other subdomain. You could even just use the TLD if you’re feeling adventurous:

```php
return array(
    '*' => array(
        'omitScriptNameInUrls' => true,
    ),

    '.test' => array(
        'devMode' => true,
    ),

    '.com' => array(
        'cooldownDuration' => 0,
    )
);
```

### Adding Multi-Environment Configs to `db.php`

All of the examples above are for craft/config/general.php, but all of the principles hold true for craft/config/db.php as well:

```php
return array(
    '*' => array(
        'tablePrefix' => 'craft',
    ),
    '.test' => array(
        'server' => 'localhost',
        'user' => 'root',
        'password' => 'letmein',
        'database' => 'buildwithcraft',
    ),
    '.com' => array(
        'server' => 'localhost',
        'user' => 'av12345',
        'password' => '$uP3r$3jp3t',
        'database' => 'av12345-buildwithcraft',
    ),
);
```

Just as with `general.php`, that `'*'` key is required to trigger multi-environment config support, even if you end up not needing it.

## Environment-Specific Variables

Something else that goes hand-in-hand with Craft’s multi-environment config support is the [environmentVariables](config-settings.md#environmentVariables) config setting. The value of this setting is an array of custom variables that can be accessed from various settings within the Control Panel.

### Example: Multi-Environment Asset Source Settings

A popular use case for the environmentVariables config setting is the File System Path and URL settings on asset sources. While these settings _can_ be relative, often times people prefer them to be absolute. In a multi-environment setup, the only way to do that is with environmentVariables.

First we need to identify the areas of these settings that will differ depending on the environment. Let’s say we only have a single Assets Source, and its settings are as follows:

* **File System Path (Local)**: `/users/brandon/Sites/example.test/public/assets/images/`
* **File System Path (Prod.)**: `/storage/av12345/www/public_html/assets/images/`
* **URL (Local)**: `http://example.test/assets/images/`
* **URL (Prod.)**: `http://example.com/assets/images/`

In each case, everything before `assets/images/` could be different between the environments. To address this, we should create two custom environment variables. Their names are completely up to us – we’ll go with “`basePath`” and “`baseUrl`”.

```php
return array(
    '*' => array(
        // ...
    ),

    'example.test' => array(
        // ...

        'environmentVariables' => array(
            'basePath' => '/users/brandon/Sites/example.test/public/',
            'baseUrl'  => 'http://example.test/',
        )
    ),

    'example.com' => array(
        // ...

        'environmentVariables' => array(
            'basePath' => '/storage/av12345/www/public_html/',
            'baseUrl'  => 'http://example.com/',
        )
    )
);
```

With those environment variables in place, we can now go into our asset source settings and type in some tags with corresponding names:

<img src="assets/environment-variables.2x.png" width="527" alt="Environment Variables 2x.">

That’s it! And the next time you need to create an asset source, you’ll be able to re-use the same `{basePath}` and `{baseUrl}` tags again – no need to manage several different asset sources’ settings individually from your config.


### Setting Support

There are only three settings within Craft that support environment variables. They are:

* File System Path (in asset source settings)
* URL (in asset source settings)
* Site URL (in Settings → General)

::: tip
It’s generally better to use the [siteUrl](config-settings.md#siteUrl) config setting for setting your site URL, rather than placing an environment variable within the Site URL setting in Settings → General. (See [this answer](http://craftcms.stackexchange.com/a/921/9) on Craft’s Stack Exchange site for an explanation.)
:::
