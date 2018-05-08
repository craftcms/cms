# Content Migrations

If your Craft project is being developed by multiple people, or has been deployed in multiple environments, managing structural changes can become a little cumbersome, as you try to keep all environments in sync with each other.

Enter content migrations. Content migrations are [migrations](http://www.yiiframework.com/doc-2.0/guide-db-migrations.html) that are written and managed for your Craft project, rather than for Craft the application, or for a plugin.

## Creating Migrations

To create a new migration, open up your terminal and go to your Craft project:

    cd /path/to/project

Then run the following command to generate a new content migration file (replacing `MIGRATION_NAME` with your migration name):

    ./craft migrate/create MIGRATION_NAME

::: tip
If your Craft install is running from a Vagrant box, you will need to SSH into the box to run this command.
:::

::: tip
Migration names must be valid PHP class names, though we recommend sticking with `snake_case` rather than `StudlyCase` as a convention.
:::

Enter `yes` at the prompt, and a new migration file will be created in a `migrations/` folder in your project root.

The migration file contains a class with two methods: `safeUp()` and `safeDown()`. `safeUp()` is where you should put the main migration code. If you want to make it possible to revert your migration, `safeDown()` is where the reversion code goes.

### Logging

If you want to log any messages in your migration code, echo it out rather than calling `Craft::info()`:

```php
echo "    > some note\n";
```

If the migration is being run from a console request, this will ensure the message is seen by whoever is executing the migration, as the message will be output into the terminal. If it’s a web request, Craft will capture it and log it to `storage/logs/` just as if you had used `Craft::info()`.

### Manipulating Database Data

Craft 3 adds a `$includeAuditColumns` argument to the [`batchInsert()`], [`insert()`], and [`update()`] migration methods (set to `true` by default) that determines whether to insert/update data in the “audit” columns (`dateCreated`, `dateUpdated`, `uid`). If the table you are inserting into does not have all three of these columns, you must pass `false` to that argument so you don’t get a SQL error.

## Executing Migrations

There are two ways to execute content migrations: from the terminal, and from the Migrations utility in the Control Panel.

To execute migrations from the terminal, go to your Craft project and run this command:

    ./craft migrate/up

::: tip
If your Craft install is running from a Vagrant box, you will need to SSH into the box to run this command.
:::

To execute migrations from the Migrations utility, go to Utilities → Migrations in the Control Panel and click the “Apply new migrations” button.

[`batchInsert()`]: http://www.yiiframework.com/doc-2.0/yii-db-migration.html#batchInsert()-detail
[`insert()`]: http://www.yiiframework.com/doc-2.0/yii-db-migration.html#insert()-detail
[`update()`]: http://www.yiiframework.com/doc-2.0/yii-db-migration.html#update()-detail
