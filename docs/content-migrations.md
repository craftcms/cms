# Content Migrations

If your Craft project is being developed by multiple people, or has been deployed in multiple environments, managing structural changes can become a little cumbersome, as you try to keep all environments in sync with each other.

Enter content migrations. Content migrations are [migrations](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations) that are written and managed for your Craft project, rather than for Craft the application, or for a plugin.

## Creating Migrations

To create a new migration, open up your terminal and go to your Craft project:

```bash
cd /path/to/project
```

Then run the following command to generate a new content migration file (replacing `<MigrationName>` with your migration name):

```bash
./craft migrate/create <MigrationName>
```

::: tip
If your Craft install is running from a Vagrant box, you will need to SSH into the box to run this command.
:::

::: tip
Migration names must be valid PHP class names, though we recommend sticking with `snake_case` rather than `StudlyCase` as a convention.
:::

Enter `yes` at the prompt, and a new migration file will be created in a `migrations/` folder in your project root.

### What Goes Inside

Migration classes contain methods: `safeUp()` and `safeDown()`. `safeUp()` is run when your migration is _applied_, and `safeDown()` is run when your migration is _reverted_.

::: tip
By default, `safeDown()` will just return `false`, meaning that reverting the migration isn’t supported. The choice to replace that with actual migration reversion code, or just leave it alone, is up to you.
:::

You can enter whatever logic you want into your migration’s `safeUp()` and `safeDown()` methods, depending on the purpose of the migration. You have full access to [Craft’s API](https://docs.craftcms.com/api/v3/), as well as any APIs provided by plugins and modules.

::: tip
Mike Hudson wrote an [excellent article](https://medium.com/@mikethehud/craft-cms-3-content-migration-examples-3a377f6420c3) about content migrations, with some examples of how to do common tasks, like create a new custom field.
:::

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
:::

### Logging

If you want to log any messages in your migration code, echo it out rather than calling `Craft::info()`:

```php
echo "    > some note\n";
```

If the migration is being run from a console request, this will ensure the message is seen by whoever is executing the migration, as the message will be output into the terminal. If it’s a web request, Craft will capture it and log it to `storage/logs/` just as if you had used `Craft::info()`.

## Executing Migrations

There are two ways to execute content migrations: from the terminal, and from the Migrations utility in the Control Panel.

To execute migrations from the terminal, go to your Craft project and run this command:

```bash
./craft migrate/up
```

::: tip
If your Craft install is running from a Vagrant box, you will need to SSH into the box to run this command.
:::

To execute migrations from the Migrations utility, go to Utilities → Migrations in the Control Panel and click the “Apply new migrations” button.
