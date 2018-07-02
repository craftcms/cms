# Control Panel Section

Plugins can provide their own section in the Control Panel by adding a `hasCpSection` property to their plugin:

```php
<?php

namespace ns\prefix;

class Plugin extends \craft\base\Plugin
{
    public $hasCpSection = true;

    // ...
}
```

::: tip
Alternatively, you can set this from your plugin’s `composer.json` file, via `extra.hasCpSection`.

```json
{
  "extra": {
    // ...
    "hasCpSection": true
  }
}
```
:::

With that in place, Craft will add a new user permission for your plugin, and users that have it will get a new item in their global Control Panel nav, pointing to `/admin/plugin-handle`.

By default, when someone goes to `/admin/plugin-handle`, Craft will look for an `index.html` or `index.twig` template within your plugin’s `templates/` directory (which should go in your plugin’s source directory).

At a minimum, that template should extend Craft’s `_layouts/cp` layout template, set a `title` variable, and override the `content` block.

```twig
{% extends "_layouts/cp" %}
{% set title = "Page Title"|t('app') %}

{% block content %}
    <p>Page content goes here</p>
{% endblock %}
```

Alternatively, you can route requests to `/admin/plugin-handle` to a controller action (or a different template) by registering a Control Panel route from your plugin’s `init()` method:

```php
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

public function init()
{
    Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
        $event->rules['plugin-handle'] = 'plugin-handle/foo/bar';
    });
}
```

## Giving Your Plugin Section a Subnav

If your CP section needs a sub-navigation in the global sidebar, you can do that by overriding your plugin’s `getCpNavItem()` method:

```php
public function getCpNavItem()
{
    $item = parent::getCpNavItem();
    $item['subnav'] = [
        'foo' => ['label' => 'Foo', 'url' => 'plugin-handle/foo'],
        'bar' => ['label' => 'Bar', 'url' => 'plugin-handle/bar'],
        'baz' => ['label' => 'Baz', 'url' => 'plugin-handle/baz'],
    ];
    return $item;
}
```

The CP templates that these subnav items resolve to can tell Craft’s `_layouts/cp.html` template which subnav item should be selected by setting the `selectedSubnavItem` variable:

```twig
{% set selectedSubnavItem = 'bar' %}
```
