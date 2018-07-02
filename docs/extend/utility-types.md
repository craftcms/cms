# Utility Types

Plugins can provide custom utility types for the Utilities section by creating a class that implements <api:craft\base\UtilityInterface>.

As a convenience, you can extend <api:craft\base\Utility>, which provides a base utility type implementation.

You can refer to Craft’s own utility classes for examples. They are located in `vendor/craftcms/cms/src/utilities/`.

## Registering Custom Utility Types

Once you have created your utility class, you will need to register it with the Utilities service, so Craft will know about it when populating the list of available utility types:

```php
<?php
namespace ns\prefix;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = MyUtility::class;
        });

        // ...
    }

    // ...
}
```
