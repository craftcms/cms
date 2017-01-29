Plugin Settings
===============

There are two ways to give your plugin its own config settings: via a config file that users can optionally save in `config/`, or via a new settings page within the Control Panel.

Both ways have their advantages:

#### Config Files

* Values can be set on a per-environment basis
* Values can be set dynamically with some PHP code
* Changes can be recorded with version control (Git)

#### Settings Pages

* Provides a significantly better administrative UX
* Values can be validated
* Plugins can execute additional code when the values change

These are not mutually exclusive options – your plugin can implement both config file-based settings as well as have its own settings page. So keep these differences in mind whenever implementing a new setting, and choose the approach that makes the most sense.

## Config File

To give your plugin support for its own config file, first you must define what the default config values should be. You do that by creating a new `config.php` file at the root of your plugin’s source folder, which returns the array of defaults:

```php
<?php

return [
    'foo' => 'defaultFooValue',
    'bar' => 'defaultBarValue',
];
```

You can then grab the config value throughout your plugin code using `craft\services\Config::get()`, passing your plugin handle as the second argument:

```php
$configSettingValue = Craft::$app->config->get('foo', 'pluginHandle');
```

Users will then be able to create a new file in their `config/` folder named after your plugin’s handle (lowercased), which returns an array that overrides whichever settings they want:

```php
<?php

return [
    'foo' => 'fooValueOverride',
];
```

## Settings Page

To give your plugin its own settings page within the Control Panel, first you will need to define a new [model](http://www.yiiframework.com/doc-2.0/guide-structure-models.html) class that will be used to hold the setting values, and validate them.

Create a `models/` directory within your plugin’s source directory, and create a `Settings.php` file within it:

```php
<?php

namespace ns\prefix\models;

class Settings extends \craft\base\Model
{
    public $foo = 'defaultFooValue';
    public $bar = 'defaultBarValue';
    
    public function rules()
    {
        return [
            [['foo', 'bar'], 'required'],
            // ...
        ];
    }
}
```

Then create a `templates/` directory within your plugin’s source directory, and create a `settings.html` file within it:

```twig
{% import "_includes/forms" as forms %}

{{ forms.textField({
    first: true,
    label: "Foo",
    name: 'foo',
    value: settings.foo
}) }}

{{ forms.textField({
    label: "Bar",
    name: 'bar',
    value: settings.bar
}) }}
```

Finally, tie everything together from your Plugin class. Its responsibilities are to:

- Tell Craft that it has settings via its `$hasSettings` property
- Instantiate the `Settings` model via its `createSettingsModel()` method
- Render the `settings.html` template via its `settingsHtml()` method

```php
<?php

namespace ns\prefix;

class Plugin extends \craft\base\Plugin
{
    public $hasSettings = true;

    protected function createSettingsModel()
    {
        return new \ns\prefix\models\Settings();
    }

    protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate('pluginHandle/settings', [
            'settings' => $this->getSettings()
        ]);
    }
    
    // ...
}
```

With all that in place, your plugin will now get its own icon on the Settings page, and a cog icon in its row on the Settings → Plugins page, which will link to `/admin/settings/plugin-handle`.

> {tip} That `plugin-handle` segment is your plugin handle, converted from `camelCase` to `kebab-case`.  

### Advanced Settings Pages

When the `/admin/settings/plugin-handle` Control Panel URL is requested, your plugin is ultimately in charge of the response. Namely, your plugin’s `getSettingsResponse()` method. The default `getSettingsResponse()` implementation in `craft\base\Plugin` will call your plugin’s `settingsHtml()` method, and then tell the active controller to render Craft’s `settings/plugins/_settings` template (the layout template for plugin settings pages), passing it the HTML returned by `settingsHtml()`.

If a plugin needs more control over its settings page(s), it can override its `getSettingsResponse()` method and do whatever it wants with the request.

It can choose to render its own template, rather than being confined to Craft’s `settings/plugins/_settings` layout template:

```php
public function getSettingsResponse()
{
    return \Craft::$app->controller->renderTemplate('pluginHandle/settings/template');
}
```

It can redirect the request to a completely different URL, too:

```php
public function getSettingsResponse()
{
    $url = \craft\helpers\UrlHelper::cpUrl('plugin-handle/settings');

    return \Craft::$app->controller->redirect($url);
}
```

Just note that whatever it returns needs to make sense as something a controller action would return, because that’s exactly what’s happening. The `craft\controllers\PluginsController::actionEditPluginSettings()` method returns whatever `getSettingsResponse()` returns directly.
