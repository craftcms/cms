# Template Roots

Modules and plugins can register custom “template roots” for either Control Panel or front-end templates.

A template root is a directory that contains templates, which are accessible to other templates from a predefined template path prefix.

For example, you could create a plugin that provides common Twig utility macros, which could be accessible from `_utils/macros.twig`.

To do that, use the [EVENT_REGISTER_SITE_TEMPLATE_ROOTS](api:craft\web\View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS) event:

```php
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use yii\base\Event;

public function init()
{
    parent::init();
    
    Event::on(
        View::class,
        View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
        function(RegisterTemplateRootsEvent $event) {
            $event->roots['_utils'] = __DIR__ . '/template-utils';
        }
    );
}
```

If you want to register new Control Panel template roots, use the [EVENT_REGISTER_CP_TEMPLATE_ROOTS](api:craft\web\View::EVENT_REGISTER_CP_TEMPLATE_ROOTS) event instead.

## Plugin Control Panel Templates

Plugins get a Control Panel template root added automatically, named after the plugin handle, which points to the `templates/` folder within the plugin’s base source folder.
