# Directory Structure

When you download a fresh copy of Craft 3, your project will have the following files and directories:

#### `config/`

Holds all of your Craft and plugin [configuration files](configuration.md), as well as your `license.key` file.

#### `modules/`

Holds any [Yii modules](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules) your site might be using.

#### `storage/`

This is where Craft stores a bunch of files that get dynamically generated at runtime.

Some of the folders you might find in there include:

- `backups/` – Stores database backups that get created when you update Craft or run the DB Backup utility.
- `logs/` – Stores Craft’s logs and PHP error logs.
- `rebrand/` – Stores the custom Login Page Logo and Site Icon files, if you’ve uploaded them.
- `runtime/` – Pretty much everything in here is there for caching and logging purposes. Nothing that Craft couldn’t live without, if the folder happened to get deleted.

  For the curious, here are the types of things you will find in craft/storage/runtime (though this is not a comprehensive list):

  - `assets/` – Stores image thumbnails, resized file icons, and copies of images stored on remote asset sources, to save Craft an HTTP request when it needs the images to generate new thumbnails or transforms.
  - `cache/` – Stores data caches.
  - `compiled_classes/` – Stores some dynamically-defined PHP classes.
  - `compiled_templates/` – Stores compiled Twig templates.
  - `mutex/` – Stores file lock data.
  - `temp/` – Stores temp files.
  - `validation.key` – A randomly-generated, cryptographically secure key that is used for hashing and validating data between requests.

#### `templates/`

Your front-end templates go in here.

#### `vendor/`

This is where all of your Composer dependencies go, including Craft itself, and any plugins you’ve installed via Composer.

#### `web/`

This directory represents your web root. (This can be renamed if needed.)

#### `.env`

This is your [PHP dotenv](https://github.com/vlucas/phpdotenv) `.env` configuration file. It defines sensitive or environment-specific config values that don’t make sense to commit to version control.

#### `.env.example`

This is your [PHP dotenv](https://github.com/vlucas/phpdotenv) `.env` file template. It should be used as a starting point for any actual `.env` files, stored alongside it but out of version control on each of the environments your Craft project is running in.

#### `.gitignore`

Tells Git which files it should ignore when committing changes.

#### `LICENSE.md`

Standard MIT license, which covers all the code in the `craftcms/craft` repo, but NOT any Composer-installed libraries in the `vendor/` folder. Feel free to delete this file.

#### `README.md`

Readme for the [craftcms/craft](https://github.com/craftcms/craft) repo. Feel free to delete this file, or replace its contents with something more relevant to your project.

#### `composer.json`

The starting point `composer.json` file that should be used for all Craft projects.

By default, there will be some settings in here that can safely be changed or removed, including:

- `name`
- `description`
- `keywords`
- `license`
- `homepage`
- `type`
- `support`

#### `composer.lock`

This is a Composer file that tells Composer exactly which dependencies and versions should be currently installed in `vendor/`.

#### `craft`

This is a command line executable that will bootstrap a Craft console application.

#### `craft.bat`

This is a Windows Command Prompt wrapper for the `craft` executable.
