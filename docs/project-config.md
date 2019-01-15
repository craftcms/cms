# Project Config

Craft 3.1 introduced the **project config**, a sharable configuration store that makes it easier for developers to collaborate and deploy site changes across multiple environments.

Craft stores the following settings in the project config:

- Asset volumes and named image transforms
- Category groups
- Craft and plugin schema versions
- Craft edition
- Email settings
- Fields and field groups
- Global sets (settings only, not their content)
- Matrix block types
- Plugin editions and settings
- Routes defined in Settings → Routes
- Sections and entry types
- Sites and site groups
- System name, time zone, and status (live/offline)
- Tag groups
- User settings and user groups

::: tip
Plugins can store additional things in the project config as well. See [Supporting Project Config](extend/project-config.md) to learn how.
:::

To take advantage of the project config on your Craft project, enable the <config:useProjectConfigFile> setting in `config/general.php`.

```php
return [
    '*' => [
        'useProjectConfigFile' => true,
    ],
];
```

::: warning
If your project is already live, make a backup of your production database and restore it on all development environments before you enable <config:useProjectConfigFile>, to ensure that all environments share the same component UIDs.
:::

Once that’s enabled, Craft will start storing the project config in a `config/project.yaml` file. Any time something changes that is managed by the project config, that file will get updated to match. And any time Craft detects that `project.yaml` has been updated on its own (e.g. if it was changed in a Git commit that was recently pulled down), any changes in it will be propagated to the local Craft install.

## Caveats

There are a few things you should keep in mind when working with the project config:

### There Will Be Composer

When Craft detects that `project.yaml` has changed, it will ensure that the versions of Craft and plugins described in the file are compatible with what’s actually installed.

If there’s a discrepancy, you will need to fix that before Craft can begin syncing the file’s changes into the loaded project config. The only practical way to do that is by running `composer install`, as access to the Control Panel will be denied until the discrepancy is resolved.

::: tip
To avoid downtime on production, you should ensure that `composer install` is built into your deployment workflow.
:::

### Production Changes May Be Forgotten

If any updates are made on production that updates `project.yaml` there, those changes will be lost the next time your project is deployed and `project.yaml` is overwritten.

To prevent that, you can set the <config:allowAdminChanges> config setting to `false` in `config/general.php`:

```php
return [
    '*' => [
        'useProjectConfigFile' => true,
    ],
    'production' => [
        // Disable project config changes on production
        'allowAdminChanges' => false,
    ], 
];
```

That will remove the UI for most administrative settings that affect the project config, and also places the project config in a read-only state, so there’s no chance that `project.yaml` will be tampered with.

### Plugins May Not Support It Yet

Any plugins that are storing configuration settings outside of their main plugin settings will need to be updated to [support the project config](extend/project-config.md). So there may still be some cases where changes need to be manually made on each environment.
