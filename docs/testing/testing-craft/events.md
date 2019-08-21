# Events

Most modules/plugins support events that are triggered that enable modules/plugins to 
place event listeners which modify actions and behaviours. This increases the flexibility and usability
of modules/plugins. 

Seeing as events and the triggering thereof are routed through some complex [Yii2](https://www.yiiframework.com/doc/guide/2.0/en/concept-events)
based functionality some specific steps must be taken to test code that triggers events. 

## Testing event code
Craft provides a simple helper method that allows you to test event codes.
Firstly you need to ensure that your test class has a `$tester` property. 
Once this class property is declared you can call the following method: 

```php
$this->tester->expectEvent(MyComponent::class, MyComponent::AN_EVENT, function() {
    MyModule::getInstance()->myComponent->myMethod();
});
```

- The first argument is the class from which your event gets triggered. Similarly to 
the first argument you pass when calling `$this->trigger()` from a class that extends 
`craft\base\Component`. 

- The second argument is the name of your event. Similarly to the second argument
you pass when calling `$this->trigger()` from a class that extends
`craft\base\Component`. 

- The third argument is is a closure that must contain the code to trigger an event. 
If the callback does not trigger the event the test will fail. 

::: tip
This is a watered down example of the `expectEvent` method. There are two optional 
parameters that give you a whole lot of extra power - see below. 
:::

## Additional properties
If you want more fine-grained control over your event testing code the `expectEvent` method
has two optional parameters. 

### `string $eventInstance = ''`
The this is the fourth property of `expectEvent`. If you want to check the class of the
event that was passed you can set this property. The property will be 
compared using [instanceof](https://www.php.net/manual/en/language.operators.type.php).

### `array $eventValues = []`
The `eventValues` property accepts an array that can be specially setup to 
validate the properties of the event that was passed. 

## Setting up `$eventValues`
Craft provides a special helper method that is recommended 
to configure `$eventValues`. You can call `$this->tester->createEventItems()`
and pass in an array with the following keys:

### `eventPropName`
The name of the event property you want to test. 
I.E. if you want to check the name of `yii\base\Event` you would set this to 
 `'name'`.

### `type`
You can choose from the following: 

```php
use craft\test\EventItem;
EventItem::TYPE_CLASS

or 

use craft\test\EventItem;
EventItem::TYPE_OTHERVALUE
```

Where
- `TYPE_CLASS` instructs Craft that when we access the `$eventPropName` on the 
passed event an object will be returned. 
- `TYPE_OTHERVALUE` instructs Craft to accept any non-object value. 

### `desiredClass`
If you have set `TYPE_CLASS` you can pass in an additional `desiredClass` property. 
Craft will then compare the returned object to the `desiredClass` property using `instanceof`. 

### `desiredValue`
`desiredValue` is designed to check the property values of the event that is passed. 
This can depend based on the `type` argument: 

#### When type is `EventItem::TYPE_OTHERVALUE`
Here you can set the `desiredValue` to anything you want. The result of 
accessing the event using the [eventPropName](#eventpropname) property will be directly compared
using `assertSame()`. 

#### When type is `EventItem::TYPE_CLASS`
Craft allows you to check the individual properties of the returned object. 
In order to do this you must enter a key-value array where: 

- `key` 
Is the name of a property belonging to the object that was returned when accessing
the event using [eventPropName](#eventpropname).

- `value`
The value that the above property above should be set to. 

::: tip
See a complete example below:

```php
use yii\base\Event;
use craft\test\EventItem;

$this->tester->expectEvent(SomeComponent::class, SomeComponent::SOME_EVENT, function() {

        // Code that should trigger an event goes here. 
    
    }, Event::class,
    $this->tester->createEventItems([
        [
            // The $event->sender prop is a \stdClass where the $key property is set to 'value'
            'eventPropName' => 'sender',
            'type' => EventItem::TYPE_CLASS,
            'desiredClass' => \stdClass::class,
            'desiredValue' => [
                'key' => 'value'
            ],
        ],
        [
            // The $event->name prop string set to 'someEvent'.
            'eventPropName' => 'name',
            'type' => EventItem::TYPE_OTHERVALUE,
            'desiredValue' => 'someEvent'
        ]
    ]
));
```
:::
