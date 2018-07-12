# Widget Types

Plugins can provide custom widget types for the Dashboard by creating a class that implements <api:craft\base\WidgetInterface> and <api:craft\base\WidgetTrait>. The class will serve both as a way to communicate various things about your widget type (with static methods), and as a model that widgets of its type will be instantiated with.

As a convenience, you can extend <api:craft\base\Widget>, which provides a base widget type implementation.

You can refer to Craft’s own widget classes for examples. They are located in `vendor/craftcms/cms/src/widgets/`.

## Registering Custom Widget Types

Once you have created your widget class, you will need to register it with the Dashboard service, so Craft will know about it when populating the list of available widget types:

```php
<?php
namespace ns\prefix;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Dashboard;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = MyWidget::class;
        });

        // ...
    }

    // ...
}
```
