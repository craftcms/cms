# Field Types

Plugins can provide custom field types by creating a class that implements <api:craft\base\FieldInterface> and <api:craft\base\FieldTrait>. The class will serve both as a way to communicate various things about your field type (with static methods), and as a model that fields of its type will be instantiated with.

As a convenience, you can extend <api:craft\base\Field>, which provides a base field type implementation.

You can refer to Craft’s own field classes for examples. They are located in `vendor/craftcms/cms/src/fields/`.

## Registering Custom Field Types

Once you have created your field class, you will need to register it with the Fields service, so Craft will know about it when populating the list of available field types:

```php
<?php
namespace ns\prefix;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = MyField::class;
        });

        // ...
    }

    // ...
}
```
