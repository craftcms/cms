# Extending Twig

Craft provides two ways for plugins to extend its Twig templating environment.

[[toc]]

## Extend the Global `craft` Variable

The global `craft` template variable is an instance of <api:craft\web\twig\variables\CraftVariable>. When a template references `craft.entries` or `craft.entries()`, it’s calling [CraftVariable::entries()](api:craft\web\twig\variables\CraftVariable::entries()) behind the scenes, for example.

The `CraftVariable` instance can be extended by plugins with [behaviors](https://www.yiiframework.com/doc/guide/2.0/en/concept-behaviors) and [services](https://www.yiiframework.com/doc/guide/2.0/en/concept-service-locator). Choosing the right approach depends on what you’re trying to add to it.

- Use a **behavior** to add custom properties or methods directly onto the `craft` variable (e.g. `craft.foo()`).
- Use a **service** to add a sub-object to the `craft` variable, which can be accessed with a custom property name, called the service’s “ID”. (e.g. `craft.foo.*`).

You can attach your behavior or service to the `CraftVariable` instance by registering an [EVENT_INIT](api:craft\web\twig\variables\CraftVariable::EVENT_INIT) event handler from your plugin’s `init()` method:

```php
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

public function init()
{
    parent::init();

    Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
        /** @var CraftVariable $variable */
        $variable = $e->sender;

        // Attach a behavior:
        $variable->attachBehaviors([
            MyBehavior::class,
        ]);

        // Attach a service:
        $variable->set('serviceId', MyService::class);
    });
}
```

## Register a Twig Extension

If you want to add new global variables, functions, filters, tags, operators, or tests to Twig, you can do that by creating a custom [Twig extension](https://twig.symfony.com/doc/2.x/advanced.html#creating-an-extension).

Twig extensions can be registered for Craft’s Twig environment by calling <api:craft\web\View::registerTwigExtension()> method:

```php
public function init()
{
    parent::init();

    if (Craft::$app->request->getIsSiteRequest()) {
        // Add in our Twig extension
        $extension = new MyTwigExtension();
        Craft::$app->view->registerTwigExtension($extension);
    }
}
```
