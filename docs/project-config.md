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

## Enabling the Project Config File

To start sharing a project config across multiple environments, follow these steps:

1. Pick a primary environment that has the most up-to-date data. (If your project is already live, this should be your production environment.)
2. Ensure that your primary environment is running the latest version of Craft.
3. If you were already running Craft 3.1 or later, run `./craft project-config/rebuild` on that environment, to ensure that its project config is up-to-date with config settings stored throughout the database.
4. Enable the <config:useProjectConfigFile> setting in `config/general.php` on your primary environment.

    ```php
    return [
        '*' => [
            'useProjectConfigFile' => true,
        ],
    ];
    ```

5. Load any page on the primary environment, so Craft can generate a `config/project.yaml` file.
6. Backup the database on the primary environment.
7. For all other environments, restore the database backup created in step 6, and save a copy of the `config/project.yaml` file created in step 5.

Going forward, Craft will start updating `config/project.yaml` any time something changes that is managed by the project config. And any time Craft detects that `project.yaml` has been updated on its own (e.g. if it was changed in a Git commit that was recently pulled down), any changes in it will be propagated to the local Craft install.

## Caveats

There are a few things you should keep in mind when working with the project config:

### There Will Be Composer

When Craft detects that `project.yaml` has changed, it will ensure that the versions of Craft and plugins described in the file are compatible with what’s actually installed.

If there’s a discrepancy, you will need to fix that before Craft can begin syncing the file’s changes into the loaded project config. The only practical way to do that is by running `composer install`, as access to the Control Panel will be denied until the discrepancy is resolved.

::: tip
To avoid downtime on production, you should ensure that `composer install` is built into your deployment workflow.
:::

### Sensitive Information Could Be Saved in `project.yaml`

Some of your system components may have required sensitive information in their settings, such as:

- a Gmail/SMTP password in your email settings
- a secret access key in an AWS S3 volume

To prevent those values from being saved into your `project.yaml` file, make sure that you are setting those fields to environment variables. See [Environmental Configuration](config/environments.md) for more information.

::: tip
If you’re overriding volume settings with `config/volumes.php`, you can set sensitive values to the environment variable name rather than calling [getenv()](http://php.net/manual/en/function.getenv.php) to avoid the real values being saved to `project.yaml`.

```php
// Bad:
'secret' => getenv('SECRET_ACCESS_KEY'),

// Good:
'secret' => '$SECRET_ACCESS_KEY',
```

Once you’ve made that change, re-save your volume in the Control Panel so your `project.yaml` file gets updated with the environment variable name.
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

### Config Data Could Get Out of Sync

If any settings managed by the project config are modified elsewhere in the database, either manually or via a plugin/module that isn’t using the appropriate service, then the project config will be out of sync with those database values, which will likely lead to errors. If that happens, Craft provides a console command that can be run to patch up your project config.

```bash
./craft project-config/rebuild
``` 
