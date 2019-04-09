# コンテンツマイグレーション

Craft プロジェクトが複数の人々によって開発されている場合、または複数の環境で開発されている場合、すべての環境を互いに同期させようとすると、構造の変更を管理するのが少し面倒になることがあります。

コンテンツマイグレーションをはじめてください。コンテンツマイグレーションとは、Craft 本体のアプリケーションやプラグインではなく、Craft プロジェクトのために書かれたり管理されているものに対しての[マイグレーション](https://www.yiiframework.com/doc/guide/2.0/en/db-migrations)です。

## マイグレーションの作成

新しいマイグレーションを作成するために、ターミナルを開き Craft プロジェクトに移動してください。

```bash
cd /path/to/project
```

次のコマンドを実行し、新しいコンテンツマイグレーションファイルを生成します（`<MigrationName>` を実際のマイグレーション名に置き換えます）。

```bash
./craft migrate/create <MigrationName>
```

::: tip
Craft のインストールが Vagrant box から実行されている場合、このコマンドを実行するために box に SSH 接続する必要があります。
:::

::: tip
マイグレーション名は有効な PHP クラス名でなければなりません。慣習として、`StudlyCase` よりも `snake_case` を使うことをお勧めします。
:::

プロンプトで `yes` と入力すると、新しいマイグレーションファイルがプロジェクトルートの `migrations/` フォルダに作成されます。

### 内部で行うこと

マイグレーションクラスには `safeUp()` と `safeDown()` メソッドが含まれます。マイグレーションが _適用される_ ときに `safeUp()` が実行され、_復帰させる_ ときに `safeDown()` が実行されます。

::: tip
デフォルトでは、`safeDown()` は `false` を返します。これは、マイグレーションの復帰はサポートされていないことを意味します。実際のマイグレーション復帰コードに置き換えるか、そのままにしておくかは、あなたの選択次第です。
:::

マイグレーションの目的に応じて、マイグレーションの `safeUp()` と `safeDown()` メソッドに必要なロジックを入力できます。プラグインやモジュールによって提供されるいかなる API と同様に、 [Craft の API](https://docs.craftcms.com/api/v3/) へフルアクセスできます。

::: tip
Mike Hudson 氏は、新しいカスタムフィールドを作成するような一般的なタスクを実行するいくつかの実例を挙げながら、コンテンツマイグレーションについて[素晴らしい記事](https://medium.com/@mikethehud/craft-cms-3-content-migration-examples-3a377f6420c3)を書かれています。
:::

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

コンテンツマイグレーションの実行には、ターミナルからとコントロールパネルのマイグレーションユーティリティからの2つの方法があります。

ターミナルからマイグレーションを実行するには、Craft プロジェクトに移動して次のコマンドを実行します。

```bash
./craft migrate/up
```

::: tip
Craft のインストールが Vagrant box から実行されている場合、このコマンドを実行するために box に SSH 接続する必要があります。
:::

マイグレーションユーティリティから実行するには、コントロールパネールで「ユーティリティ > マイグレーション」に移動し、「新しいマイグレーションを適用」ボタンをクリックします。

