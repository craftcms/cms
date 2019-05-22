# Events

Most modules/plugins support events that are triggered that enable modules/plugins to 
place event listeners which modify actions and behaviours. This increases the flexibility and usability
of modules/plugins. 

Seeing as events and the triggering thereof are routed through some complex [Yii2](https://www.yiiframework.com/doc/guide/2.0/en/concept-events)
base functionality some specific steps must be taken to test code that triggers events. 

## Testing event code
Craft provides a simple helper method that allows you to test event codes.
Firstly you need to ensure that your test class has a `$tester` property. 
Once this class property is declared you can call the following method: 

```php
use Craft;

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
