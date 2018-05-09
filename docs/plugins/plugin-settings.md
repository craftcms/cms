# Plugin Settings

There are two ways to give your plugin its own settings: via a config file that users can optionally save in `craft/config/`, or via a new settings page within the Control Panel.

Both ways have their advantages:

**Config Files**

* Values can be set on a [per-environment basis](../multi-environment-configs.md)
* Values can be set dynamically with some PHP code
* Changes can be recorded with version control (Git)

**Settings Pages**

* Provides a significantly better administrative UX
* Values can be validated
* Plugins can execute additional code when the values change

These are not mutually exclusive options – your plugin can implement both config file-based settings as well as have its own settings page. So keep these differences in mind whenever implementing a new setting, and choose the approach that makes the most sense.

## Config File

To give your plugin support for its own config file, first you must define what the default config values should be. You do that by creating a new `config.php` file at the root of your plugin’s folder, which returns an array of the default values:

```php
<?php

return array(
    'foo' => 'defaultFooValue',
    'bar' => 'defaultBarValue',
);
```

You can then grab the config value throughout your plugin code using [ConfigService::get()](https://docs.craftcms.com/api/v2/services/ConfigService.html#get-detail), passing your plugin handle (lowercased) as the second argument:

```php
$configSettingValue = craft()->config->get('foo', 'cocktailrecipes');
```

Users will then be able to create a new file in their `craft/config/` folder, called `yourpluginhandle.php` (lowercased), which returns an array that overrides whichever settings they want:

```php
<?php

return array(
    'foo' => 'fooValueOverride',
);
```

## Settings Page

To give your plugin its own settings page within the Control Panel, first define which settings the plugin actually has. You do that by creating a protected [defineSettings()](https://docs.craftcms.com/api/v2/etc/components/BaseSavableComponentType.html#defineSettings-detail) method within your primary plugin class. This method returns an array whose keys define the setting names, and values define the parameters (the type of value, etc.).

```php
<?php
namespace Craft;

class CocktailRecipesPlugin extends BasePlugin
{
    // ...

    protected function defineSettings()
    {
        return array(
            'cocktailCategories' => array(AttributeType::Mixed, 'default' => array('Sours', 'Fizzes', 'Juleps')),
        );
    }
}
```

Next you need to add a public [getSettingsHtml()](https://docs.craftcms.com/api/v2/etc/components/ISavableComponentType.html#getSettingsHtml-detail) method which returns the HTML for displaying your settings. We recommend that you create a template for the actual settings HTML, and load it up with [TemplatesService::render()](https://docs.craftcms.com/api/v2/services/TemplatesService.html#render-detail).

```php
<?php
namespace Craft;

class CocktailRecipesPlugin extends BasePlugin
{
    // ...

    public function getSettingsHtml()
    {
       return craft()->templates->render('cocktailrecipes/settings', array(
           'settings' => $this->getSettings()
       ));
   }
}
```
