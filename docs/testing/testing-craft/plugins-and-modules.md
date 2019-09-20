# Modules & Plugins

## Modules
Setting up modules for testing is quite simple. In your config directory ensure 
an `app.php` is present. Once this file exists you register your Module exactly 
[how you would](../../extend/module-guide.md) 
do this for a normal Craft project. Once setup, modules will be loaded in your test suite
and available through the `MyModule::getInstance()` call. 

## Plugins
Plugins must be registered via the `codeception.yml` file in accordance with the
[configuration options](../framework/config-options.md#plugins).
Once it is registered the plugin will be installed and available through the 
`MyPlugin::getInstance()` call. 

::: tip
Unsure how to setup tests for your Module/Plugin? [Click here](getting-started.md)
:::
