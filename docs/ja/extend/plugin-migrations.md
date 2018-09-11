# Plugin Migrations

If your schema changes over the life of your plugin, you can write a [migration](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations) to keep existing installations updated with the latest schema. Craft automatically checks for new migrations whenever a plugin’s schema version number changes.

[[toc]]

## マイグレーションの作成

To create a new migration, open up your terminal and go to a Craft project that your plugin is installed in:

```bash
cd /path/to/project
```

Then run the following command to generate a new migration file for your plugin (replacing `<MigrationName>` and `<PluginHandle>` with your migration name and plugin handle, respectively):

```bash
./craft migrate/create <MigrationName> --plugin=<PluginHandle>
```

::: tip
Craft のインストールが Vagrant box から実行されている場合、このコマンドを実行するために box に SSH 接続する必要があります。
:::

::: tip
マイグレーション名は有効な PHP クラス名でなければなりません。慣習として、`StudlyCase` よりも `snake_case` を使うことをお勧めします。
:::

Enter `yes` at the prompt, and a new migration file will be created in a `migrations/` subfolder within your plugin’s source directory.

### 内部で行うこと

マイグレーションクラスには `safeUp()` と `safeDown()` メソッドが含まれます。マイグレーションが _適用される_ ときに `safeUp()` が実行され、_復帰させる_ ときに `safeDown()` が実行されます。

::: tip
You can safely ignore the `safeDown()` method, as Craft doesn’t have a way to revert plugin migrations from the Control Panel.
:::

You have full access to [Craft’s API](https://docs.craftcms.com/api/v3/) from your `safeUp()` method, but be careful about using your own plugin’s APIs here. As your plugin’s database schema changes over time, so will your API’s assumptions about the schema. If an old migration calls a service method that relies on database changes that haven’t been applied yet, it will result in a SQL error. So in general you should execute all SQL queries directly from your own migration class. It may feel like you’re duplicating code, but it will be more future-proof.

### データベースデータの操作

マイグレーションクラスは <api:craft\db\Migration> を拡張し、データベースを操作するためのいくつかのメソッドを提供しています。マイグレーションメソッドはどちらも使いやすく、ターミナルにステータスメッセージを出力するため、<api:craft\db\Command> よりもこれらを使う方が良いでしょう。

```php
// Bad:
$this->db->createCommand()
    ->insert('{{%tablename}}', $rows)
    ->execute();

// Good:
$this->insert('{{%tablename}}', $rows);
```

::: warning
<api:api:yii\db\Migration::insert()>、[batchInsert()](api:craft\db\Migration::batchInsert())、および、[update()](api:yii\db\Migration::update()) マイグレーションメソッドは、引数 `$columns` で指定したものに加えて `dateCreated`、 `dateUpdated`、`uid` テーブルのカラムにあるデータを自動的に挿入 / アップデートします。操作しているテーブルにこれらのカラムがない場合、引数 `$includeAuditColumns` に `false` を渡して、SQL エラーにならないようにしてください。
:::

::: tip
<api:craft\db\Migration> はデータを _選択する_ ためのメソッドを持たないため、Yii の[クエリビルダー](https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder)を通す必要があります。

```php
use craft\db\Query;

$result = (new Query())
    // ...
    ->all();
```

:::

### ロギング

マイグレーションコード内でメッセージを記録したい場合、`Craft::info()` を呼び出すよりも echo で出力してください。

```php
echo "    > some note\n";
```

マイグレーションがコンソールリクエストから実行された場合、メッセージがターミナル内に出力されるため、マイグレーションを実行している人がそのメッセージを見ることを保証します。ウェブリクエストであれば、`Craft::info()` を使用したときと同様に、Craft がそれを取得して `storage/logs/` に記録します。

## マイグレーションの実行

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

