# Introduction

Craft comes with a powerful plugin framework coupled with a robust set of APIs that paves the way for a wide variety of plugins.

## Assumed Knowledge

Craft plugins are very much like little applications in and of themselves. We’ve made it as simple as we can, but the training wheels are off. A little prior knowledge is going to be required to write a plugin.

For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL, as well as some semi-advanced concepts like [object-oriented programming](https://en.wikipedia.org/wiki/Object-oriented_programming) and [PHP namespaces](http://php.net/manual/en/language.namespaces.php).

## Anatomy of a Plugin

There are several different classes your plugin can have, which handle different aspects of your plugins’ duties. They all aren’t necessary; you can pick and choose which of these you actually need when writing your plugin.

### Primary Plugin Class

Your [primary plugin class](setting-things-up.md#your-primary-plugin-class) tells Craft some basic information about the plugin, such as its name and version number. It also handles a few oddball things like installation/uninstallation and hooks.

::: tip
This is the only required plugin class. For some plugins, it may even be the only file that’s needed.
:::

### Controllers

If your plugin will be handling incoming HTTP requests, it will need a [controller](controllers.md). Controllers have “action functions” that will automatically get their own HTTP endpoints that forms and URLs can point to.

There’s no limit to what your controllers’ action functions can do, but they are typically just middlemen between the outside world and your plugin’s [services](services.md).

### Services

If your plugin provides any core functionality, it should be placed within a [service](services.md). Services’ public methods are available globally throughout the system, for both your plugin and other plugins to access. They can be accessed via `craft()->serviceHandle->methodName()`.

### Variables

You can think of [variables](variables.md) as the template-facing version of [services](services.md). Your primary variable class can be accessed from any template via `craft.pluginName`.

### Models

Any data that your plugin manages should be represented by [models](models.md), and you should use them to pass that data around between your [controllers](controllers.md), [services](services.md), and templates.

### Records

If your plugin needs to store data in its own database table, you should create a [record](records.md). Records have two purposes: When your plugin gets installed, Craft will refer to them in order to know which custom tables it should create and how they should be designed, and your plugin can use them to select, update, and insert data within that table.

### Field Types

If your plugin provides a new type of custom field, it can do that with a [field type](field-types.md). Field types handle all of the type-specific functions of the field, such as its settings, what the input HTML should be, and how its data should be stored.

### Dashboard Widgets

If your plugin provides a new type of dashboard widget, it can do that with a [widget](widgets.md). Widgets handle all of the type-specific functions of the dashboard widget, such as its settings and what the body HTML should be.
