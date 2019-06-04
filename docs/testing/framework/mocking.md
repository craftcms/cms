# Mocking
Most of the mocking support Craft provides is inherited through 
[Codeception](https://codeception.com/docs/reference/Mock) and 
[PHP Unit](https://phpunit.de/manual/6.5/en/test-doubles.html).
Their documentation provides all the basic information you need to get started.

Additionally Craft provides some minor additional support to help mock your 
dependencies and improve your tests.

## `mockMethods`
The parameters you can pass into `mockMethods` are: 

#### **Module $module** 
An instance of the `yii\base\Module` class to which your 
mocked component will be set. 
#### **string $component** 
The class of the component that you want to mock
#### **array $methods = []** 
An array of methods where the `key` is the method name
and the `value` is the returned result - can also be a callback function.
#### **array $constructParams = []** 
 The parameters that must be passed into the constructor 
when creating the mock. 

<hr>

Let's say you have a module/plugin called `Mailchimp` that facilitates 
an integration between Craft and Mailchimp. For this integration you may need
to create a method called `getUsersFromMailchimp` that makes a GET request to Mailchimp.
Now in your test, you don't want to *actually* make a GET request to the Mailchimp
servers. Let's say all requests to the Mailchimp servers are done via a
[service](../../extend/services.md)
called `Externals` 

Now seeing as you don't want to make call the Mailchimp servers in a test environment
you would have to mock this method. For this, you can use `mockMethods`. 
Mocking your `Externals` service would look something like this: 

```php
$this->tester->mockMethods([
    Mailchimp::getInstance(),
    Externals::class,
    [
        'getUsersFromMailchimp' => [['user1'], ['user2']],
    ],
    []
]);
```

What the above would do is ensure that if 
`Mailchimp::getInstance()->externals->getUsersFromMailchimp()`
is called in your tests the value `[['user1'], ['user2']]` will always be returned.
No querying to the Mailchimp servers will be done. This ensures predictable tests
that are more performant.

You can call this method multiple times in a single test if you want to mock out 
multiple components. 

::: tip
Under the hood `mockMethods` uses `Codeception\Stub::construct()`.  You can read more 
about this method in the Codeception documentation.
:::
## `mockCraftMethods`
`mockCraftMethods` is a pass through function that calls `mockMethods`. 
`mockCraftMethods` can be called via `$this->tester` within your tests. 
The only difference is that the `Craft::$app` object 
is passed as the first argument into the
`mockMethods` call. Argument 2, 3 and 4 of `mockMethods` are applicable and available
within `mockCraftMethods`.

## Full mock
Craft provides a `fullMock` setting that can be enabled in your `codeception.yml` file. 
A full explanation of this setting is given [here](full-mock.md).
The `fullMock` option ensures all components in `Craft::$app` are set to mocks using
PHP Unit. 
If you prefer to isolate *all* your dependencies during testing
this option is for you. `fullMock` also mocks any modules/plugins you define. 
