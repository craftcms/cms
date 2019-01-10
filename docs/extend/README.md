# Extending Craft

Craft provides a complete toolkit for customizing its features and functionality – almost every aspect of Craft can be extended, hooked into, or completely replaced. If you know PHP, you can build anything you want with it.

## Modules vs. Plugins

Most customizations come in the form of a **module** or a **plugin**.

As Yii’s documentation [puts it](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules), **modules** are _“self-contained software units that consist of models, views, controllers, and other supporting components”_. In other words, modules extend the system in various ways, without needing to change any of the core system code.

Modules can be simple, serving a single purpose like providing a new [Dashboard widget type](widget-types.md), or they can be complex, introducing entirely new concepts to the system, like an e-commerce application.

**Plugins** are a Craft-specific concept, so you won’t find any mention of it in the Yii docs. They can do everything modules can do (plugins actually _are_ modules, technically), and some other things that make them better for being publicly distributed:

- They can be installed/trialed/purchased from the Craft Plugin Store.
- They can make database changes when installed, updated, or uninstalled.
- They get their own settings page within the Settings section of the Control Panel.
- They can be enabled/disabled by an admin, without running any Composer commands.

If the thing you want to build would benefit from those features, make it a plugin. Otherwise, a module might be better.
