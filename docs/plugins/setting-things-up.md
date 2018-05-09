# Setting Things Up

{intro} There are a few simple steps that you must go through each time you create a new plugin.

## Pick a Name and Handle

The first step in creating a plugin is to pick a user-facing name. Craft doesn’t really have any restrictions on what that can be, besides that it must be textual.

Once you have a name, you need to decide on your plugin’s **handle**. This will be used as a prefix for all of your plugin’s class names. It should be based on your plugin’s user-facing name, except without any spaces, hyphens, underscores, or punctuation, and it must use StudlyCase.

Here are some examples of plugin names and handles:

| User-Facing Name  | Handle
| ----------------- | -----------------
| Cocktail Recipes  | `CocktailRecipes`
| P&T Field Pack    | `PtFieldPack`
| ACME Tennis Balls | `AcmeTennisBalls`


## Create Your Plugin’s Folder

All of a plugin’s files are contained within a single folder, including its front-end resources. That folder lives in craft/plugins/.

Name your folder after your plugin’s handle, except it should be **completely lowercase**.

## Your Primary Plugin Class

There’s only one mandatory file that each plugin needs, and that file defines your plugin’s **primary class**. This class tells Craft some basic information about your plugin: its name, version number, its developer’s name and URL, etc.

Create the file at the root of your plugin directory. It should be named with your plugin’s handle, plus “Plugin.php”, e.g. “CocktailRecipesPlugin.php”.

Here’s a sample primary plugin file:

```php
<?php
namespace Craft;

class CocktailRecipesPlugin extends BasePlugin
{
    function getName()
    {
         return Craft::t('Cocktail Recipes');
    }

    function getVersion()
    {
        return '1.0';
    }

    function getDeveloper()
    {
        return 'Pixel & Tonic';
    }

    function getDeveloperUrl()
    {
        return 'http://pixelandtonic.com';
    }
}
```

## Plugin Icons

If you want your plugin to have a custom icon in the Control Panel, create a `resources` folder for your plugin and place an `icon.svg` file in it.

Plugins with Control Panel sections can have a custom icon in the global sidebar by placing an `icon-mask.svg` file within their `resources` folder.

### Methods

Your primary plugin class can define the following methods:

#### `defineSettings()`:

Defines the attributes that model your plugin’s available settings. (See [plugin-settings](plugin-settings.md) for more information.)

#### `getDescription()`:

Returns a description of the plugin.

#### `getDeveloper()`:

Returns the developer’s name.

#### `getDeveloperUrl()`:

Returns the developer’s website URL.

#### `getDocumentationUrl()`:

Returns a website URL to the plugin’s online documentation.

#### `getName()`:

Returns the user-facing name.

If your plugin’s name should be translatable, wrap it in `Craft::t()` (see [Internationalization](internationalization.md)). For example, “Cocktail Recipes” would be a good candidate for translation support, but a proper noun like “Akismet” might not.

#### `getReleaseFeedUrl()`:

Returns the plugin’s releases JSON feed URL.

 If the plugin wants to have its updates included in the Updates page, it should provide a JSON feed in the following format:

```javascript
[
    {
        "version": "0.9.0",
        "downloadUrl": "https://download.craftcommerce.com/0.9/Commerce0.9.0.zip",
        "date": "2015-12-01T10:00:00-08:00",
        "notes": [
            "# Big Stuff",
            "[Added] It’s now possible to create new products right from Product Selector Modals (like the ones used by Products fields).",
            "[Improved] Variants are now defined in a new Variant Matrix field, right on the main Edit Product pages.",
            "# Bug Fixes",
            "[Fixed] Fixed a Twig error that occurred if you manually went to /commerce/orders/new. You now receive a 404 error instead."
        ]
    },
    {
        "version": "0.9.1",
        "downloadUrl": "https://download.craftcommerce.com/0.9/Commerce0.9.1.zip",
        "date": "2015-12-01T11:00:00-08:00",
        "notes": [
            "[Fixed] Fixed a PHP error that occurred when creating a new produt when the current user’s username was ‘null’."
        ]
    }
]
```

 Notes:

- The feed must be valid JSON.
- The feed’s URL must begin with “https://” (so it is fetched over SSL).
- Each release must contain `version`, `downloadUrl`, `date`, and `notes` attributes.
- Each release’s `downloadUrl` must begin with “https://” (so it is downloaded over SSL).
- Each release’s `date` must be an ISO-8601-formatted date, as defined by either [DateTime::ATOM](http://php.net/manual/en/class.datetime.php#datetime.constants.atom) or [DateTime::ISO8601](http://php.net/manual/en/class.datetime.php#datetime.constants.iso8601)  (with or without the colon between the hours and minutes of the timezone offset).
- `notes` can either be a string (with each release note separated by a newline character), or an array.
- Release note lines that begin with `#` will be treated as headings.
- Release note lines that begin with `[Added]`, `[Improved]`, or `[Fixed]` will be given `added`, `improved`, and `fixed` classes within the Updates page.
- Release note lines can contain Markdown code, but not HTML.
- Releases can contain a `critical` attribute which can be set to `true` if the release is critical.

#### `getSettingsHtml()`:

Returns the HTML that displays your plugin’s settings. (See [plugin-settings](plugin-settings.md) for more information.)

#### `getSettingsUrl()`:

Returns a URL to your plugin’s settings. This can be used if your plugin requires more control over its settings than you get with `getSettingsHtml()`. If it returns anything, your plugin’s name within Settings → Plugins will link to the returned URL.

```php
public function getSettingsUrl()
{
    return 'myplugin/settings';
}
```

#### `getSourceLanguage()`:

Returns the source language that your plugin was written in. (Defaults to `'en_us'`.)

```php
public function getSourceLanguage()
{
    return 'de';
}
```

#### `getVersion()`:

Returns the plugin’s version number.

#### `getSchemaVersion()`:

If the plugin has database tables, returns the database schema version number for the plugin.  Incrementing this version number will trigger any new migrations your plugin has to run.

#### `hasCpSection()`:

Returns whether the plugin should get its own tab in the CP header. (See [templates](templates.md) for information on that.)

#### `onAfterInstall()`:

Called right after your plugin’s row has been stored in the `plugins` database table, and tables have been created for it based on its [records](records.md).

#### `onBeforeInstall()`:

Called right before your plugin’s row gets stored in the `plugins` database table, and tables have been created for it based on its [records](records.md).

#### `onBeforeUninstall()`:

Called right after your plugin’s record-based tables have been deleted, and its row in the `plugins` table has been deleted.
