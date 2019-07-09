# Configuration

The Craft module is configured through the `codeception.yml` file. 

::: tip
The Craft module inherits all configuration options from the 
[Yii2 codeception module](https://codeception.com/for/yii). 
All its [configuration options](https://codeception.com/docs/modules/Yii2) 
are thus also available to use and not explained here.
:::

## Config options
### `projectConfig`

Accepts: Object

The `projectConfig` option instructs the Craft module if and how to set-up the `project.yml`
file in the `config` directory specified in `CRAFT_CONFIG_PATH`. 
The `projectConfig` setting accepts an object with the following parameters: 

- file (Required): What file the project config setting must be copied from. This is not the `project.yml` file in 
`CRAFT_CONFIG_PATH` but instead the file whose contents will be copied into the `project.yml` file 
located here. 

::: warning
If you have enabled `projectConfig`, regular DB based fixtures may cause syncing issues. It is recommended
 to setup your environment using the `project.yml` file only. 
:::

### `migrations`

Accepts: Array|Object

The `migrations` parameter accepts an Array of objects with the following parameters. 

- class (Required): The migration class
- params: Any parameters that must be passed into the migration. 

Migrations will be applied before any tests are run.

### `plugins`

Accepts: Array|Object

The `plugins` parameter accepts an Array of objects with the following parameters. 

- class (Required): [The main plugin class](../../extend/plugin-guide.html#the-plugin-class)
- handle (Required): The plugin handle

Plugins will be installed before any tests are run.

### `setupDb`

Accepts: Object

The `setupDb` parameter controls how the database is setup before tests. 
It accepts an object with the following parameters.  

- clean (Required): Whether all tables should be deleted before any tests 
- setupCraft (Required): Whether the `Install.php` migration should be run  before any tests. 

### `edition`
Accepts: int

Determines what edition Craft must be in when running your tests and what is returned when calling 
`Craft::$app->getEdition()`. Note if `projectConfig`
is enabled the `edition` property will be ignored.
To set an edition you must define the desired edition in the `project.yml` instead.

## PHP Constants
Additionally, you will have to define several PHP Constants for the test suite to use. All of these
constants must be defined in the `tests/_bootstrap.php`. 

### `CRAFT_STORAGE_PATH`
The [storage path](directory-structure.md#storage) Craft can use during testing.

### `CRAFT_TEMPLATES_PATH`
The [templates path](directory-structure.md#templates) Craft can use during testing.

### `CRAFT_CONFIG_PATH`
The [config path](directory-structure.md#config) Craft can use during testing.

::: warning
If you are testing an actual Craft site this directory cannot be the config directory you use for
the production site. I.E. Ensure it is located within the `tests/_craft/` folder. 
:::

### `CRAFT_MIGRATIONS_PATH`
The path to the folder where all your [migration](extend/migrations.md) classes are stored. 

### `CRAFT_TRANSLATIONS_PATH`
The path to the folder where all your [translations](static-translations.md) are stored.

### `CRAFT_VENDOR_PATH`
Path to the [vendor](directory-structure.html#vendor) directory.

