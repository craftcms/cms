# マイグレーション

マイグレーションは、システムに一時的な変更を加える PHP クラスです。

ほとんどの場合、Craft のマイグレーションは [Yii の実装](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations)と同様に機能しますが、Yii と異なり、Craft は3種類のマイグレーションを管理します。

- **アプリケーションのマイグレーション** – Craft 独自の内部的なマイグレーション
- **プラグインのマイグレーション** – インストールされたプラグインが持つ独自のマイグレーショントラック
- **コンテンツのマイグレーション** - Craft プロジェクト自体もマイグレーションできます。

## マイグレーションの作成

::: tip
Craft のインストールが Vagrant box から実行されている場合、これらのコマンドを実行するために box に SSH 接続する必要があります。
:::

プラグインやプロジェクトのための新しいマイグレーションを作成するために、ターミナルを開き Craft プロジェクトに移動してください。

```bash
cd /path/to/project
```

それから、プラグインのための新しいマイグレーションファイルを生成するために次のコマンドを実行します（`<migration_name>` を snake_case のマイグレーション名に、`<plugin-handle>` を kebab-case のプラグインハンドルに置き換えます）。

::: code

```bash Plugin Migration
./craft migrate/create <migration_name> --plugin=<plugin-handle>
```

```bash Content Migration
./craft migrate/create <migration_name>
```

:::

プロンプトで `yes` と入力すると、新しいマイグレーションファイルが作成されます。 コマンドによって出力されたファイルパスで見つけることができます。

プラグインのマイグレーションの場合、プラグインの[スキーマバージョン](api:craft\base\PluginTrait::$schemaVersion)を上げてください。そうすることで、Craft は新しいバージョンにアップデートするように新しいプラグインのマイグレーションをチェックすることを知ります。

### 内部で行うこと

マイグレーションクラスには [safeUp()](api:yii\db\Migration::safeUp()) と [safeDown()](api:yii\db\Migration::safeDown()) メソッドが含まれます。マイグレーションが _適用される_ ときに `safeUp()` が実行され、_復帰させる_ ときに `safeDown()` が実行されます。

::: tip
コントロールパネルから Craft がマイグレーションを元に戻す方法がないため、通常 `safeDown()` メソッドは無視できます。
:::

`safeUp()` メソッドから [Craft の API](https://docs.craftcms.com/api/v3/) にフルアクセスできますが、プラグインのマイグレーションはここでプラグイン独自の API を呼び出すことを避けるようにする必要があります。長い間にプラグインのデータベーススキーマが変化するように、スキーマに関する API の想定も変化します。古いマイグレーションが、まだ適用されていないデータベースの変更を前提とするサービスメソッドを呼び出すと、SQL エラーをもたらすでしょう。そのため、一般的には独自のマイグレーションクラスからすべての SQL クエリを直接実行する必要があります。コードを複製しているように感じるかもしれませんが、将来的にも保証されるでしょう。

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

マイグレーションコード内でメッセージを記録したい場合、[Craft::info()](api:yii\BaseYii::info()) を呼び出すよりも echo で出力してください。

```php
echo "    > some note\n";
```

マイグレーションがコンソールリクエストから実行された場合、メッセージがターミナル内に出力されるため、マイグレーションを実行している人がそのメッセージを見ることを保証します。ウェブリクエストであれば、`Craft::info()` を使用したときと同様に、Craft がそれを取得して `storage/logs/` に記録します。

## マイグレーションの実行

ターミナルから Craft に新しいマイグレーションを適用することができます。

::: code

```bash Plugin Migration
./craft migrate/up --plugin=<plugin-handle>
```

```bash Content Migration
./craft migrate/up
```

:::

または、すべてのマイグレーショントラックを通じて Craft にすべての新しいマイグレーションを適用することもできます。

```bash
./craft migrate/all
```

Craft はコントロールパネルのリクエストで新しい[スキーマバージョン](api:craft\base\PluginTrait::$schemaVersion)を持つプラグインの新しいプラグインのマイグレーションをチェックし、コンテンツのマイグレーションはコントロールパネルの「ユーティリティ > マイグレーション」から適用できます。

## プラグインのインストールマイグレーション

プラグインは、プラグインのインストールとアンインストールで処理される特別な「インストール」マイグレーションを持つことができます。インストールマイグレーションは、通常のマイグレーションと並行して `migrations/Install.php` にあります。次のテンプレートに従うべきです。

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

マイグレーション名「`install`」を渡すと、`migrate/create` コマンドでプラグインにインストールマイグレーションを与えることができます。

```bash
./craft migrate/create install --plugin=<plugin-handle>
```

プラグインがインストールマイグレーションを持つ場合、`safeUp()` メソッドはプラグインがインストールされるときに呼び出されます。そして、`safeDown()` メソッドはプラグインがアンインストールされるときに呼び出されます（プラグインの [install()](api:craft\base\Plugin::install())、および、[uninstall()](api:craft\base\Plugin::uninstall()) メソッドによって呼び出されます）。

::: tip
`plugins` データベーステーブルの行を管理するのはプラグインの責任 *ではありません*。Craft がそれをケアします。
:::

### デフォルトのプロジェクトコンフィグデータの設定

直接、または、プラグインの API を経由して、インストール時に [project config](project-config.md) に何かを追加したい場合、必ず新しい `project.yaml` ファイルにそのプラグインのレコードを持っていないことを確認してください。

```php
public function safeUp()
{
    // ...

    // Don't make the same config changes twice
    if (Craft::$app->projectConfig->get('plugins.<plugin-handle>', true) === null) {
        // Make the config changes here...
    }
}
```

なぜなら、プロジェクトコンフィグの同期の一部としてプラグインがインストールされている可能性があるためです。インストールマイグレーションが独自にプロジェクトコンフィグを変更する場合、`project.yaml` からの新しい変更をすべて上書きしてしまいます。

