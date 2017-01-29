Control Panel Section
=====================

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

With that in place, Craft will add a new user permission for your plugin, and users that have it will get a new item in their global Control Panel nav, pointing to `/admin/plugin-handle`.

> {tip} That `plugin-handle` segment is your plugin handle, converted from `camelCase` to `kebab-case`.

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
public function init()
{
    yii\base\Event::on(
        craft\web\UrlManager::class,
        craft\web\UrlManager::EVENT_REGISTER_CP_URL_RULES,
        function(craft\events\RegisterUrlRulesEvent $event) {
            $event->rules['plugin-handle'] = 'plugin-handle/foo/bar';
        }
    );
}
```
