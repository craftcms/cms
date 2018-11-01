# Hooks and Events

There are two ways for plugins to be invoked at different points in the core code, or in another plugin’s code: *hooks* and *events*.

## What’s the difference?

**Hooks** allow a plugin to interact with the code that called it. They are called with the assumption that data will be returned, and the originating code will usually loop through the returned data immediately after calling the hook.

**Events**, on the other hand, are only called to announce when a particular action has taken place. They give plugins an opportunity to run their own event-handling logic at that point, without directly affecting the originating code in any way.

## Hooks

### Latching onto Hooks

To latch onto a hook, all you need to do is add a new method to your plugin’s primary class with the same name as the hook.

```php
function registerCpRoutes()
{
    return array(
        'cocktails/new'               => 'cocktails/_edit',
        'cocktails/(?P<widgetId>\d+)' => 'cocktails/_edit',
    );
}
```

### Creating Hooks

To create a hook, just call `craft()->plugins->call('yourHookName')`. That function will loop through all installed plugins, check to see if they have a method with the same name as your hook, and if so, call it, and include the plugin’s response in an array. `call()` will then return the array of the plugins’ responses back to your plugin.

```php
$pirates = craft()->plugins->call('registerPirates');
```

`call()` optionally takes a second argument: an array of arguments that should be passed on to each of the hook methods.

```php
$pirates = craft()->plugins->call('registerPirates', array('smee'));
```

### Hooks Reference

See [hooks-reference](hooks-reference.md) for a list of available hooks.

## Events

Craft uses [events](https://www.yiiframework.com/wiki/327/events-explained) to announce when certain things have taken place.

### Listening for Events

Your plugin can register new event listeners anywhere it wants to, but the `init()` function in your primary plugin class is going to be the most reliable place, since that function will get called on every request, before any events have had a chance to fire.

Listen for events with `craft()->on()`. That’s a wrapper for Yii’s internal event handler, with the added benefit of not initializing the target class if it hasn’t been loaded yet.

For example, if your plugin were to attach an event in the [traditional way](https://www.yiiframework.com/wiki/327/events-explained):

```php
craft()->entries->onSaveEntry = function(Event $event) {
    // ...
};
```

…then the EntriesService would need to get initialized on every single request, regardless of whether the request has anything to do with entries.

Here’s how you would listen for the same event using `craft()->on()`:

```php
craft()->on('entries.saveEntry', function(Event $event) {
    // ...
});
```

If EntriesService has already been initialized, your event will get attached to it right away, just as if you had set the event in the traditional way. However if it has *not* been initialized yet, your event handler will be filed away. If EntriesService *does* get initialized further down the road, Craft will instantly add your event handler to it.

If you want to access the arguments passed into an event, you would do so through the `$event->params` array.

For example, in the above `saveEntry` example, `$event->params['entry']` is an EntryModel of the entry that was just saved and `$event->params['isNewEntry']` is a boolean set to true or false.

### Events Reference

See [events-reference](events-reference.md) for a list of available events.
