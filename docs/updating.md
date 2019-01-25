# Updating Instructions

## Updating from the Control Panel

When an update is available, users with the permission to update Craft will see a badge in the CP next to the Utilities navigation item in the sidebar. Click on Utilities and then choose Updates. You can also use the Updates widget on the Control Panel dashboard, which is installed by default.

This section displays both Craft CMS updates and plugin updates. Each update has its own Update button. Clicking that will initiate Craft’s self-updating process.

You can run all of the updates (Craft, all plugin updates available) using the Update All button at the top left of the Updates page.

## Updating from the Terminal

Craft 3.0.38 and 3.1.4 introduced a new `update` console command that can be used to update Craft and plugins.

To see available updates, go to your Craft project in your terminal and run this command:

```bash
./craft update
```

![An example interaction with the `update` command.](./images/cli-update-info.png)

To update everything all at once, run this command:

```bash
./craft update all
```

To update a specific thing, replace `all` with its handle (either `craft` to update Craft, or a plugin’s handle).

```bash
./craft update element-api
```

![An example interaction with the `update <handle>` command.](./images/cli-update-plugin.png)

You can also pass multiple handles in at once:

```bash
./craft update element-api commerce
```

By default, Craft will update you to the latest available version. To update to a specific version, append `:<version>` to the handle:

```bash
./craft update element-api:2.5.4
```

Craft also provides an `update/composer-install` command, which behaves like the `composer install` command, but doesn’t require you to have Composer installed.
