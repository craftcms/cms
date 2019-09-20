# Element Action Types

Plugins can provide custom action types for element index pages by creating a class that implements <api:craft\base\ElementActionInterface>. The class will serve both as a way to communicate various things about your action type (with static methods), and as a model that actions of its type will be instantiated with.

As a convenience, you can extend <api:craft\base\ElementAction>, which provides a base action type implementation.

You can refer to Craft’s own element action classes for examples. They are located in `vendor/craftcms/cms/src/elements/actions/`.

## Registering Custom Element Action Types

To get an element action to show up on an element index page, it has to be registered with the element type.

If it’s for a custom element type that is defined by the same plugin, simply include your element action in the element class’s [defineActions()](element-types.md#index-page-actions) method.

If it’s for an element type that is out of the plugin’s control, you can register it using the `registerActions` event:

```php
<?php
namespace ns\prefix;

use craft\base\Element;
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
            $event->actions[] = MyAction::class;
        });

        // ...
    }

    // ...
}
```
