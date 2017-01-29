Directory Structure
===================

When a Craft project is initiated using the [`craftcms/craft`](https://github.com/craftcms/craft) Composer project, it will have the following files and directories:

#### `config/` 

Holds all of your Craft and plugin [configuration files](configuration.md), as well as your `license.key` file.

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

This directory represents your web root.

#### `.env.example`

This is your [PHP dotenv](https://github.com/vlucas/phpdotenv) `.env` file template. It should be used as a starting point for any actual `.env` files, stored alongside it but out of version control on each of the environments your Craft project is running in.

#### `.gitignore`

Tells Git which files it should ignore when committing changes.

#### `LICENSE.md`

Standard MIT license, which covers all the code in the `craftcms/craft` repo, but NOT any Composer-installed libraries in the `vendor/` folder. Feel free to delete this file.

#### `README.md`

Readme for the `craftcms/craft` repo. Feel free to delete this file.

#### `composer.json`

The starting point `composer.json` file that should be used for all Craft projects. In addition to requiring Craft CMS itself, it also requires [`craftcms/plugin-installer`](https://github.com/craftcms/plugin-installer), [`vlucas/phpdotenv`](https://github.com/vlucas/phpdotenv), and [`roave/security-advisories`](https://github.com/Roave/SecurityAdvisories), and adds [asset-packagist.org](https://asset-packagist.org/) as a custom Composer repository (used to load Bower and NPM packages via Composer).

#### `craft`

This is a command line executable that will bootstrap a Craft console application.

#### `craft.bat`

This is a Windows Command Prompt wrapper for the `craft` executable.
