# プラグインマイグレーション

スキーマがプラグインのを寿命を超えて変化した場合、既存のインストールを最新のスキーマでアップデートするために[マイグレーション](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations)を作成します。Craft はプラグインのスキーマバージョン番号が変わるごとに、新しいマイグレーションを自動的にチェックします。

[[toc]]

## マイグレーションの作成

新しいマイグレーションを作成するには、ターミナルを開き、プラグインがインストールされている Craft プロジェクトに移動します。

```bash
cd /path/to/project
```

それから、プラグインのための新しいマイグレーションファイルを生成するために次のコマンドを実行します（`<MigrationName>` と `<PluginHandle>` をそれぞれマイグレーション名とプラグインハンドルに置き換えます）。

```bash
./craft migrate/create <MigrationName> --plugin=<PluginHandle>
```

::: tip
Craft のインストールが Vagrant box から実行されている場合、このコマンドを実行するために box に SSH 接続する必要があります。
:::

::: tip
マイグレーション名は有効な PHP クラス名でなければなりません。慣習として、`StudlyCase` よりも `snake_case` を使うことをお勧めします。
:::

プロンプトで `yes` と入力すると、新しいマイグレーションファイルがプラグインのソースディレクトリ内の `migrations/` サブフォルダに作成されます。

### 内部で行うこと

マイグレーションクラスには `safeUp()` と `safeDown()` メソッドが含まれます。マイグレーションが _適用される_ ときに `safeUp()` が実行され、_復帰させる_ ときに `safeDown()` が実行されます。

::: tip
Craft にはコントロールパネルからプラグインのマイグレーションを元に戻す方法がないため、`safeDown()` メソッドを無視しても問題ありません。
:::

`safeUp()` メソッドから [Craft の API](https://docs.craftcms.com/api/v3/) に完全にアクセスできますが、ここでプラグイン独自の API を使用することに注意してください。長い間にプラグインのデータベーススキーマが変化するように、スキーマに関する API の想定も変化します。古いマイグレーションが、まだ適用されていないデータベースの変更を前提とするサービスメソッドを呼び出すと、SQL エラーをもたらすでしょう。そのため、一般的には独自のマイグレーションクラスからすべての SQL クエリを直接実行する必要があります。コードを複製しているように感じるかもしれませんが、将来的にも保証されるでしょう。

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

プラグインのマイグレーションを実行するには、スキーマバージョンを増やす必要があります。（プラグインのスキーマバージョンを明示的に定義していない場合、デフォルトで `1.0.0` になります。）

```php
class Plugin extends \craft\base\Plugin
{
    public $schemaVersion = '1.0.1';

    // ...
}
```

ファイルをあるべき場所に用意したら、コントロールパネルに移動すると、Craft が保留中のプラグインマイグレーションを実行するよう促すでしょう。これを実行するには「完了」をクリックしてください。

あるいは、`migrate/up` コマンドでターミナルから保留中のマイグレーションを実行できます。

```bash
./craft migrate/up --plugin=<plugin-handle>
```

## インストールマイグレーション

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
./craft migrate/create install --plugin=<PluginHandle>
```

プラグインがインストールマイグレーションを持つ場合、`safeUp()` メソッドはプラグインがインストールされるときに呼び出されます。そして、`safeDown()` メソッドはプラグインがアンインストールされるときに呼び出されます（<api:craft\base\Plugin::install()> と `uninstall()` によって行使されます）。

::: tip
`plugins` データベーステーブルの行を管理するのはプラグインの責任 *ではありません*。Craft がそれをケアします。
:::

