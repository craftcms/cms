# Assertion helpers

Testing mainly revolves around `asserting` that some behaviour, object or value, is working in an expected manner 
or with an expected result. With Craft introducing some key concepts 
the Craft module introduces it's own set of `assert` / `expect` methods allowing
you to write more concise and effective tests. 

## `assertElementsExist`

This assert can be used to test that multiple [elements](../../extend/element-types.md#getting-started) exist in the database I.E as a result of code you have just executed. 
It accepts three arguments: 

- `string $elementType` what [element type](../../extend/element-types.md) must these elements be of. 
- `array $searchParameters` the params that they must match (see below). 
- `int $amount = 1` the amount of elements that must be found. 
- `bool $searchAll = false` whether `anyStatus()` and `trashed(null)` should be used when searching for possible Elements. 
Do so if you, for example, need to search for a newly created User who will have `User::STATUS_DRAFT` as applicable status. 

::: tip
Under the hood the Craft module will call `$elementType::find()` creating a `craft\elements\db\ElementQuery`. 
Then it will apply the `$searchParameters` with the `$key` being the property of the `ElementQuery` and `$value` what
that property must be set to. 
:::

## `assertTestFailed`

This assert is useful for if you are providing support resources for developers who work with your module/plugin. 
I.E. if you provide an `assert` method of your own for an element type your module/plugin introduces. It 
accepts two arguments: 

- `callable $callback` a callable in which a test should fail (I.E `$this->assertTrue(false)`). 
- `string $message = ''` the message the test should fail with. 
## `expectEvent`

Please see our separate page on [events testing](../testing-craft/events.md) for everything you need to know about
testing for events. 

## `assertPushedToQueue`

See the separate page on [queue testing](../testing-craft/queue.md) for everything you need to know
about testing the [queue](../../config/app.md#queue-component). 
