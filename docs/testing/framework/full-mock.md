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

TODO: Implement and document how this works for modules and plugins. 


