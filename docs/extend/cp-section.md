# Control Panel Section

Modules and plugins can add new sections to the Control Panel using the [EVENT_REGISTER_CP_NAV_ITEMS](api:craft\web\twig\variables\Cp::EVENT_REGISTER_CP_NAV_ITEMS) event:

```php
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use yii\base\Event;

public function init()
{
    parent::init();

    Event::on(
        Cp::class,
        Cp::EVENT_REGISTER_CP_NAV_ITEMS,
        function(RegisterCpNavItemsEvent $event) {
            $event->navItems[] = [
                'url' => 'section-url',
                'label' => 'Section Label',
                'icon' => '@ns/prefix/path/to/icon.svg',
            ];
        }
    );

    // ...
}
```

Each item within the [navItems](api:craft\events\RegisterCpNavItemsEvent::$navItems) array can have the following keys:

- `url` – The URL that the nav item should link to. (It will be run through <api:craft\helpers\UrlHelper::cpUrl()>.)
- `label` – The user-facing nav item label.
- `icon` – The path to the icon SVG that should be used. (It can begin with an alias.)
- `badgeCount` _(optional)_ – The badge count that should be displayed in the nav item.
- `subnav` _(optional)_ – An array of subnav items that should be visible when your section is accessed. (See [Subnavs](#subnavs).)

## Subnavs

If your section has a sub-navigation, each subnav item within your `subnav` array should be represented by a sub-array with `url` and `label` keys:

```php
'subnav' => [
    'foo' => ['label' => 'Foo', 'url' => 'section-url/foo'],
    'bar' => ['label' => 'Bar', 'url' => 'section-url/bar'],
    'baz' => ['label' => 'Baz', 'url' => 'section-url/baz'],
],
```

Your templates can specify which subnav item should be selected by setting a `selectedSubnavItem` variable to the key of the nav item:

```twig
{% set selectedSubnavItem = 'bar' %}
```

## Plugin Sections

Plugins that only need to add one section can set a `$hasCpSection` property on their primary plugin class, rather than using the [EVENT_REGISTER_CP_NAV_ITEMS](api:craft\web\twig\variables\Cp::EVENT_REGISTER_CP_NAV_ITEMS) event:

```php
<?php

namespace ns\prefix;

class Plugin extends \craft\base\Plugin
{
    public $hasCpSection = true;

    // ...
}
```

You can modify aspects of the plugin’s Control Panel nav item by overriding its [getCpNavItem()](api:craft\base\PluginInterface::getCpNavItem()) method:

```php
public function getCpNavItem()
{
    $item = parent::getCpNavItem();
    $item['badgeCount'] = 5;
    $item['subnav'] = [
        'foo' => ['label' => 'Foo', 'url' => 'plugin-handle/foo'],
        'bar' => ['label' => 'Bar', 'url' => 'plugin-handle/bar'],
        'baz' => ['label' => 'Baz', 'url' => 'plugin-handle/baz'],
    ];
    return $item;
}
```

If you do this, Craft will automatically add a new [user permission](user-permissions.md) for your plugin, and only show the nav item for users that have it.

Clicking on a plugin’s section will take the user to `/admin/plugin-handle`, which will attempt to load an `index.html` or `index.twig` template within the plugin’s [template root](template-roots.md) (its `templates/` folder within its base source folder).

::: tip
See [Control Panel Templates](cp-templates.md) for more information about developing Control Panel templates.
:::

Alternatively, you can route `/admin/plugin-handle` requests to a controller action (or a different template) by registering a Control Panel route from your plugin’s `init()` method:

```php
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

public function init()
{
    Event::on(
        UrlManager::class,
        UrlManager::EVENT_REGISTER_CP_URL_RULES,
        function(RegisterUrlRulesEvent $event) {
            $event->rules['plugin-handle'] = 'plugin-handle/foo/bar';
        }
    );
}
```
