# Creating Database Migrations

If your schema changes over the life of your plugin, you can write a **migration** to keep existing installations updated with the latest schema. Craft automatically checks for new migrations whenever a plugin’s version number changes.

While not required, Craft does come with a handy shell script to get you started. To create a new migration, open up Terminal, and run the following commands:

```bash
cd /path/to/craft/app/etc/console
chmod 777 yiic
./yiic migrate create <MIGRATION-DESCRIPTION> <PLUGIN-HANDLE>
```

The `<MIGRATION-DESCRIPTION>` should contain letters, digits and/or underscore characters only.

The `<PLUGIN-HANDLE>` is the name of your primary plugin class minus the word 'Plugin'.  For 'CocktailRecipesPlugin', it would be 'CocktailRecipes'.

That will create a new migration file in your plugins’ `migrations/` folder (and create that folder if it doesn’t exist), which will look something like this:

```php
<?php
namespace Craft;

class m121019_144608_pluginHandle_migrationDesc extends BaseMigration
{
    public function safeUp()
    {
        return true;
    }
}
```

If you don’t use `yiic`, you can manually create a migration file with the following pattern:

```
mYYMMDD_HHmmSS_pluginHandle_migrationDesc
```

Where `YY` is the two digit year, `MM` is the two digit month, `DD` is the two digit day, `HH` is the two digit hour, `mm` is the two digit minute and `SS` is the two digit second.

Put your migration code in that `safeUp()` function.

## `yiic migrate` commands

A full list of `yiic migrate` commands are as follows:

### `yiic migrate create <MIGRATION-DESCRIPTION> <PLUGIN-HANDLE>`

Creates a new migration in your plugins’ `migrations/` folder with the given migration description.

### `yiic migrate history <PLUGIN-HANDLE>`

Shows a list of migrations that have already been ran for this plugin.

### `yiic migrate new <PLUGIN-HANDLE>`

Shows a list of migrations that have not been applied yet, but should be.

### `yiic migrate up <PLUGIN-HANDLE>`

Runs all new migrations for a plugin.

Craft will automatically attempt to run any new migrations for a plugin if it detects that the plugin’s database schema version number in it’s `*Plugin.php` file is greater than the plugin’s database schema version in the craft_plugins table in the database.

## Installing new Records

If you add entirely new records to your plugin, the tables will automatically get created for people just installing your plugin.  For users that already have your plugin installed, you will need to write a migration that adds the tables and any foreign key constraints that will get added during their next update.

We've made this fairly painless with this `yiic` command:

```bash
yiic querygen all <RECORD-CLASS-NAME>
```

This will generate the SQL necessary to create the table and any necessary foreign key constraints that you can insert right into the `safeUp()` method in your migration.

Please note that we do **not** recommend using records directly within a migration.
