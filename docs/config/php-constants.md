# PHP Constants

Your `web/index.php` file can define certain PHP constants, which Craft’s bootstrap script will check for while loading and configuring Craft.

### `CRAFT_BASE_PATH`

The path to the **base directory** that Craft will look for `config/`, `templates/`, and other directories within by default. (It is assumed to be the parent of the `vendor/` directory by default.)

### `CRAFT_COMPOSER_PATH`

The path to `composer.json`. (It is assumed to live within the base directory by default.)

### `CRAFT_CONFIG_PATH`

The path to the `config/` directory. (It is assumed to live within the base directory by default.)

### `CRAFT_CONTENT_MIGRATIONS_PATH`

The path to the `migrations/` directory used to store content migrations. (It is assumed to live within the base directory by default.)

### `CRAFT_ENVIRONMENT`

The environment ID that multi-environment configs can reference when defining their environment-specific config values. (`$_SERVER['SERVER_NAME']` will be used by default.)

See [Environmental Configuration](environments.md) for more details on how this is used.

### `CRAFT_LICENSE_KEY`

Your Craft license key, if for some reason that must be defined by PHP rather than a license key file. (Don’t set this until you have a valid license key.)

### `CRAFT_LICENSE_KEY_PATH`

The path that Craft should store its license key file, including its filename. (It will be stored as `license.key` within your `config/` directory by default.)

### `CRAFT_SITE`

The Site handle or ID that Craft should be serving from this `index.php` file. (Only set this if you have a good reason to. Craft will automatically serve the correct site by inspecting the requested URL, unless this is set.)

### `CRAFT_STORAGE_PATH`

The path to the `storage/` directory. (It is assumed to live within the base directory by default.)

### `CRAFT_TEMPLATES_PATH`

The path to the `templates/` directory. (It is assumed to live within the base directory by default.)

### `CRAFT_TRANSLATIONS_PATH`

The path to the `translations/` directory. (It is assumed to live within the base directory by default.)

### `CRAFT_VENDOR_PATH`

The path to the `vendor/` directory. (It is assumed to live 4 directories up from the bootstrap script by default.)
