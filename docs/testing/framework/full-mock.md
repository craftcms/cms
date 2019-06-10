# Fully mocking the `Craft::$app` object. 

Most of the Craft testing framework resolves around an integration style of testing. 
Inherently this means we perform a lot of setup work in order to make it appear
like you are working with an ordinary installation of Craft - as much as possible at least. 
This means that the database is set up to mirror the latest version of the `install.php` script
run once Craft is installed a `Craft::$app` object is set up and works exactly the same 
as it would if you were running Craft in a production environment. This is the case for both
unit, functional and acceptance testing. 

Obviously, this forces your unit tests into a specific style which may not prefer.
For this reason, the Craft Codeception module provides the `fullMock` option
to be set
in your `codeception.yaml` or `unit.suite.yaml` file. If you set this option to true 
instead of creating a `Craft::$app` that is as close to the real thing Craft will 
setup the `Craft::$app` object and all services in points to using 
PHP Unit's mocking system.

Under the hood Craft will setup a mock of each service that is set on the `Craft::$app` 
using `craft\test\TestSetup:getMockApp()`. The _actual_ mock is setup using 
`craft\test\TestSetup:getMock()`. 

::: warning
Some Craft module methods such as [consoleCommand](../testing-craft/console.md) may not work as expected with `fullMock` on. 
Please be aware of the implications of enabling this option. If you are just starting testing
use the [getting started guide](../testing-craft/getting-started.md) 
to first get an overview of what tests are and 
what the differences are between `fullMock` on and off.  
:::

## Plugins and modules
If you enable `fullMock` your module/plugin and its components will be mocked
up similarly to Craft.

In order to support mocking of components within modules and/or plugins, you need to add 
a `getComponentMap` method in your main class. This method must return an array 
containing sub-arrays which meet the following specifications: 

- `string` The class of the service
- `array` An array containing, in the mentioned order: 
  - `string` The name of the method used to access this service. I.E if you access
  your module/plugin's service as follows: `MyModule::getInstance()->getMyService()`
  you would enter 'getMyService' for this parameter. Leave null if you don't access
  your module/plugin via methods. 
  - `string` The property name used to access this service. I.E
  if you access your module/plugin's service as follows:
   `MyModule::getInstance()->myService` you would enter 'myService' in this parameter. 
   Leave null if not applicable.  
   
The parameters must be entered in the order as mentioned above. 
::: tip
See an example map for the `craft\services\Elements` service below. 

```php
return [
    [Elements::class, ['getElements', 'elements']],
];
```
More examples shown in `craft\test\TestSetup:getCraftServiceMap()`. 
:::
