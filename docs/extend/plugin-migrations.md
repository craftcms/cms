# Plugin Migrations

If your schema changes over the life of your plugin, you can write a [migration](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations) to keep existing installations updated with the latest schema. Craft automatically checks for new migrations whenever a plugin’s schema version number changes.

[[toc]]

## Creating Migrations

To create a new migration, open up your terminal and go to a Craft project that your plugin is installed in:

```bash
cd /path/to/project
```

Then run the following command to generate a new migration file for your plugin (replacing `<MigrationName>` and `<PluginHandle>` with your migration name and plugin handle, respectively):

```bash
./craft migrate/create <MigrationName> --plugin=<PluginHandle>
```

::: tip
If your Craft install is running from a Vagrant box, you will need to SSH into the box to run this command.
:::

::: tip
Migration names must be valid PHP class names, though we recommend sticking with `snake_case` rather than `StudlyCase` as a convention.
:::

Enter `yes` at the prompt, and a new migration file will be created in a `migrations/` subfolder within your plugin’s source directory.

### What Goes Inside

Migration classes contain methods: `safeUp()` and `safeDown()`. `safeUp()` is run when your migration is _applied_, and `safeDown()` is run when your migration is _reverted_.

::: tip
You can safely ignore the `safeDown()` method, as Craft doesn’t have a way to revert plugin migrations from the Control Panel.
:::

You have full access to [Craft’s API](https://docs.craftcms.com/api/v3/) from your `safeUp()` method, but be careful about using your own plugin’s APIs here. As your plugin’s database schema changes over time, so will your API’s assumptions about the schema. If an old migration calls a service method that relies on database changes that haven’t been applied yet, it will result in a SQL error. So in general you should execute all SQL queries directly from your own migration class. It may feel like you’re duplicating code, but it will be more future-proof.

### Manipulating Database Data

Your migration class extends <api:craft\db\Migration>, which provides several methods for working with the database. It’s better to use these than their <api:craft\db\Command> counterparts, because the migration methods are both simpler to use, and they’ll output a status message to the terminal for you.

```php
// Bad:
$this->db->createCommand()
    ->insert('{{%tablename}}', $rows)
    ->execute();

// Good:
$this->insert('{{%tablename}}', $rows);
```  

::: warning
The <api:api:yii\db\Migration::insert()>, [batchInsert()](api:craft\db\Migration::batchInsert()), and [update()](api:yii\db\Migration::update()) migration methods will automatically insert/update data in the `dateCreated`, `dateUpdated`, `uid` table columns in addition to whatever you specified in the `$columns` argument. If the table you’re working with does’t have those columns, make sure you pass `false` to the `$includeAuditColumns` argument so you don’t get a SQL error.
:::

::: tip
<api:craft\db\Migration> doesn’t have a method for _selecting_ data, so you will still need to go through Yii’s [Query Builder](https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder) for that.

```php
use craft\db\Query;

$result = (new Query())
    // ...
    ->all();
```

### Logging

If you want to log any messages in your migration code, echo it out rather than calling `Craft::info()`:

```php
echo "    > some note\n";
```

If the migration is being run from a console request, this will ensure the message is seen by whoever is executing the migration, as the message will be output into the terminal. If it’s a web request, Craft will capture it and log it to `storage/logs/` just as if you had used `Craft::info()`.

## Executing Migrations

To execute your plugin’s migrations, you’ll need to increase its schema version. (If you haven’t already explicitly defined your plugin’s schema version, it will be `1.0.0` by default.)

```php
class Plugin extends \craft\base\Plugin
{
    public $schemaVersion = '1.0.1';

    // ...
}
```

With that in place, go to your Control Panel, and Craft will prompt you to run any pending plugin migrations. Click “Finish up” to do that.

Alternatively, you can run pending migrations from your terminal with the `migrate/up` command:

```bash
./craft migrate/up --plugin=<plugin-handle>
```

## Install Migrations

Plugins can have a special “Install” migration which handles the installation and uninstallation of the plugin. Install migrations live at `migrations/Install.php` alongside normal migrations. They should follow this template:

```php
<?php
namespace ns\prefix\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        // ...
    }

    public function safeDown()
    {
        // ...
    }
}
```

You can give your plugin an install migration with the `migrate/create` command if you pass the migration name “`install`”:

```bash
./craft migrate/create install --plugin=<PluginHandle>
```

When a plugin has an Install migration, its `safeUp()` method will be called when the plugin is installed, and its `safeDown()` method will be called when the plugin is uninstalled (invoked by <api:craft\base\Plugin::install()> and `uninstall()`).

::: tip
It is *not* a plugin’s responsibility to manage its row in the `plugins` database table. Craft takes care of that for you.
:::
