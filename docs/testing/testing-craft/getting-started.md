# Getting started

In order to get started please ensure you have a basic understanding of the following:
- Craft CMS 3.2 or higher
- [Codeception](https://codeception.com/docs/01-Introduction)
- [Composer](https://getcomposer.org/)
- [Yaml](https://learnxinyminutes.com/docs/yaml/)

This guide can be used for setting up tests for a Craft site & Modules/Plugins. 
<hr>

[[toc]]

## Step 1: Install Codeception 3.x

Follow instruction 1 and 2 Codeception's [website](https://codeception.com/quickstart)
::: tip
If you are setting up tests for a Craft CMS Site and not a module/plugin, 
when running `composer install` on production, add the `--no-dev` flag to avoid
installing Codeception and its support libraries.
:::

## Step 2: Setting up the required directories
The Craft module requires a directory setup that is very similar to what an ordinary 
Craft project [requires](../../directory-structure.md)

- In your `tests/` folder create a folder called `_craft` 
- In the `_craft` folder create the following folders:
    - storage
    - templates
    - migrations
    - config
    - translations

::: tip
Testing a Craft site? There is a good chance you can copy-paste your translations, templates, migrations, and config folder into the `_craft` folder.
:::

## Step 3: Setup required files

### Codeception.yml
Open the `codeception.yml` file and replace its content with the following: 

```yaml
actor: Tester
paths:
    tests: tests
    log: tests/_output
    data: tests/_data
    support: tests/_support
    envs: tests/_envs
settings:
    bootstrap: _bootstrap.php
params:
    - tests/.env
modules:
    config:
        \craft\test\Craft:
            configFile: 'tests/_craft/config/test.php'
            entryUrl: 'https://your-site-url.test/'
            entryScript: 'index.php'
            projectConfig: {}
            migrations: []
            plugins: []
            cleanup: true
            transaction: true
            dbSetup: {clean: true, setupCraft: true}
```

::: tip
These variables are explained [here](../framework/config-options.md)
:::

### .env
Create a `.env` file in `tests/` and ensure the following variables are setup:

```dotenv
DB_DRIVER=""
DB_SERVER=""
DB_USER=""
DB_PASSWORD=""
DB_DATABASE=""
DB_SCHEMA=""
DB_TABLE_PREFIX=""
DB_PORT=""
SECURITY_KEY=""
DEFAULT_SITE_URL="https://your-site-url.test/" # Set this to the `entryUrl` param in the `codeception.yml` file.
```

::: tip
You will need to have the `vlucas/phpdotenv` library installed with a minimum version of `^3.0`. 
You can install this package using `composer require --dev vlucas/phpdotenv ^3.0`.
:::
### Craft constants
Create a `_bootstrap.php` file in your `tests/` directory and add the following: 
```php
<?php

use craft\test\TestSetup;

ini_set('date.timezone', 'UTC');

// Use the current installation of Craft
define('CRAFT_STORAGE_PATH', __DIR__ . '/_craft/storage');
define('CRAFT_TEMPLATES_PATH', __DIR__ . '/_craft/templates');
define('CRAFT_CONFIG_PATH', __DIR__ . '/_craft/config');
define('CRAFT_MIGRATIONS_PATH', __DIR__ . '/_craft/migrations');
define('CRAFT_TRANSLATIONS_PATH', __DIR__ . '/_craft/translations');
define('CRAFT_VENDOR_PATH', dirname(__DIR__).'/vendor');

TestSetup::configureCraft();
```

### Required `Craft::$app` setup code
Add a `test.php` file in the `tests/_craft/config` directory you created earlier. 
In this PHP file add the following: 

```php
<?php
use craft\test\TestSetup;

return TestSetup::createTestCraftObjectConfig();
```

### Enable the Craft module
In your `tests` folder there are the following files: 
- acceptance.suite.yml
- functional.suite.yml
- unit.suite.yml

In each of them ensure that the Craft codeception module is enabled as follows: 

```yaml
modules:
    enabled:
        - \craft\test\Craft
        # Don't delete any other enabled modules. 
```

## Setup required config

Craft requires a `db.php` file to be present in the config directory. To satisfy this requirement
add a `db.php` file in `tests/_craft/config/` and fill it with the following: 
```php
<?php

return [
    'password' => getenv('DB_PASSWORD'),
    'user' => getenv('DB_USER'),
    'database' => getenv('DB_DATABASE'),
    'tablePrefix' => getenv('DB_TABLE_PREFIX'),
    'driver' => getenv('DB_DRIVER'),
    'port' => getenv('DB_PORT'),
    'schema' => getenv('DB_SCHEMA'),
    'server' => getenv('DB_SERVER'),
];
```

The `tests/_craft/config` dir acts entirely the same as a normal `config/` folder in a Craft application. 
The only difference is you cannot create a different configuration for web and console requests (I.E `app.web.php` vs `app.console.php`). 
If, for example, the project you are testing defines custom routes you can create a `routes.php` file
which will be included in the craft configuration when setting up the test environment. 

## Codeception requirement
Create a `_bootstrap.php` file in the following folders: 
- `tests/unit`
- `tests/functional`
- `tests/acceptance`

And just place a `<?php` tag in the file. 

::: tip
`_bootstrap` in the specific directories will only run once Codeception is told to run that
type of test. You can thus tailor setup work you only want to be done for a specific test suite 
in these `_bootstrap.php` files. i.e. if you want specific PHP code to run only before unit tests place it in the `tests/unit/_bootstrap.php` file.
:::

## Step 4: Adding a test
Codeception provides a generation script for various types of tests which can 
be found in their [documentation](https://codeception.com/docs/02-GettingStarted).

For the purpose of this guide let's create one test manually. Create a `ExampleTest.php` 
file in `tests/unit/` and add the following content.
```php
<?php

namespace yournamespace\tests;

use Codeception\Test\Unit;

use UnitTester;
use Craft;

class ExampleTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testExample()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition());
    }
}
```


## Step 5: Liftoff
Run the following command:

``` bash
codecept run unit
```

This will take a second to load as it has to install all of Craft's database tables. 
Once completed it will say:

> OK (1 test, 1 assertion)

## Conclusion
We have just created a unit test and tested a small part of the Craft codebase.
 
Codeception provides two other types of testing. A basic overview can be 
found in the Codeception documentation.

The Craft documentation contain many other useful tips for testing as well as explaining various Craft specific testing concepts. 
It is recommended you give them a good read before writing tests for your production code. 
