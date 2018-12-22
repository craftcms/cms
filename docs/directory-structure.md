# Directory Structure

When you download a fresh copy of Craft 3, your project will have the following folders and files in it:

### `config/`

Holds all of your Craft and plugin [configuration files](config/README.md), as well as your `license.key` file.

::: tip
You can customize the name and location of this folder by setting the [CRAFT_CONFIG_PATH](config/php-constants.md#craft-config-path) PHP constant in `web/index.php`.
:::

### `modules/`

Holds any [Yii modules](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules) your site might be using.

### `storage/`

This is where Craft stores a bunch of files that get dynamically generated at runtime.

Some of the folders you might find in there include:

- `backups/` – Stores database backups that get created when you update Craft or run the DB Backup utility.
- `logs/` – Stores Craft’s logs and PHP error logs.
- `rebrand/` – Stores the custom Login Page Logo and Site Icon files, if you’ve uploaded them.
- `runtime/` – Pretty much everything in here is there for caching and logging purposes. Nothing that Craft couldn’t live without, if the folder happened to get deleted.

  For the curious, here are the types of things you will find in `storage/runtime/` (though this is not a comprehensive list):

  - `assets/` – Stores image thumbnails, resized file icons, and copies of images stored on remote asset volumes, to save Craft an HTTP request when it needs the images to generate new thumbnails or transforms.
  - `cache/` – Stores data caches.
  - `compiled_classes/` – Stores some dynamically-defined PHP classes.
  - `compiled_templates/` – Stores compiled Twig templates.
  - `mutex/` – Stores file lock data.
  - `temp/` – Stores temp files.
  - `validation.key` – A randomly-generated, cryptographically secure key that is used for hashing and validating data between requests.

::: tip
You can customize the name and location of this folder by setting the [CRAFT_STORAGE_PATH](config/php-constants.md#craft-storage-path) PHP constant in `web/index.php`.
:::

### `templates/`

Your front-end Twig templates go in here. Any local site assets, such as images, CSS, and JS that should be statically served, should live in the [web](directory-structure.md#web) folder.

::: tip
You can customize the name and location of this folder by setting the [CRAFT_TEMPLATES_PATH](config/php-constants.md#craft-templates-path) PHP constant in `web/index.php`.
:::

### `vendor/`

This is where all of your Composer dependencies go, including Craft itself, and any plugins you’ve installed via Composer.

::: tip
You can customize the name and location of this folder by changing the [CRAFT_VENDOR_PATH](config/php-constants.md#craft-vendor-path) PHP constant in `web/index.php`.
:::

### `web/`

This directory represents your server’s webroot. The public `index.php` file lives here and this is where any of the local site images, CSS, and JS that is statically served should live.

::: tip
You can customize the name and location of this folder. If you move it so it’s no longer living alongside the other Craft folders, make sure to update the [CRAFT_BASE_PATH](config/php-constants.md#craft-vendor-path) PHP constant in `<Webroot>/index.php`. 
:::

### `.env`

This is your [PHP dotenv](https://github.com/vlucas/phpdotenv) `.env` configuration file. It defines sensitive or environment-specific config values that don’t make sense to commit to version control.

### `.env.example`

This is your [PHP dotenv](https://github.com/vlucas/phpdotenv) `.env` file template. It should be used as a starting point for any actual `.env` files, stored alongside it but out of version control on each of the environments your Craft project is running in.

### `.gitignore`

Tells Git which files it should ignore when committing changes.

### `composer.json`

The starting point `composer.json` file that should be used for all Craft projects. See the [Composer documentation](https://getcomposer.org/doc/04-schema.md) for details on what can go in here.

### `composer.lock`

This is a Composer file that tells Composer exactly which dependencies and versions should be currently installed in `vendor/`.

### `craft`

This is a command line executable that will bootstrap a Craft console application.

### `craft.bat`

This is a Windows Command Prompt wrapper for the `craft` executable.
