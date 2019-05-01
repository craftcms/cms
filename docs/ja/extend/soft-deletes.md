# ソフトデリート

このガイドに従うことで、モジュールとプラグインはコンポーネントにソフトデリートのサポートを追加できます。

::: tip
すべてのエレメントタイプは、ソフトデリートをそのままサポートできます。復元できるようにするための情報は、[エレメントタイプ](element-types.md#restore-action)を参照してください。
:::

## データベーステーブルの準備

ソフトデリート可能なコンポーネントは、データベーステーブルに `dateDeleted` カラムを持たなければなりません。`dateDeleted` 値を持つ行は、ソフトデリートされたとみなされます。

```php
// New table migration
$this->createTale('{{%tablename}}', [
    // other columns...
    'dateDeleted' => $this->dateTime()->null(),
]);

// Existing table migration
$this->addColumn('{{%tablename}}', 'dateDeleted',
    $this->dateTime()->null()->after('dateUpdated'));
```

ソフトデリート可能なコンポーネントデータを含むテーブルは、（主キー以外に）固有の制約を適用するべきではありません。もしそうしているなら、それらを削除する必要があります。

```php
use craft\helpers\MigrationHelper;

// Stop enforcing unique handles at the database level
MigrationHelper::dropIndexIfExists('{{%tablename}}', ['handle'], true, $this);
$this->createIndex(null, '{{%tablename}}', ['handle'], false);
```

## 時間が経過したら、行を完全に削除

ソフトデリートされたテーブル行は、コンフィグ設定 <config:softDeleteDuration> にセットされた期間だけ保持され、その後完全に削除されるべきです。

すべてのリクエストごとに古い行をチェックするのではなく、Craft の[ガベージコレクション](../gc.md)ルーチンの一部にできます。

<api:craft\services\Gc> は、実行されるたびに `run` イベントを発火します。あなたのモジュール / プラグインの `init()` メソッドから、それを利用できます。

```php
use craft\services\Gc;
use yii\base\Event;

public function init()
{
    paren::init();
    
    Event::on(Gc::class, Gc::EVENT_RUN, function() {
        Craft::$app->gc->hardDelete('{{%tablename}}');
    }
}
```

[hardDelete()](api:craft\services\Gc::hardDelete()) メソッドは、`dateDeleted` 値にコンフィグ設定 <config:softDeleteDuration> よりも古いタイムスタンプがセットされたすべての行を削除します。

::: tip
複数のテーブルで古い行をチェックする必要がある場合、代わりに [hardDelete()](api:craft\services\Gc::hardDelete()) へテーブル名の配列を渡すことができます。
:::

## Active Record クラスのアップデート

コンポーネントが対応する [Active Record](https://www.yiiframework.com/doc/guide/2.0/en/db-active-record) クラスを持つ場合、<api:craft\db\SoftDeleteTrait> をインポートすることによってソフトデリートサポートを追加できます。

```php
use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class MyRecord extends ActiveRecord
{
    use SoftDeleteTrait;
    
    // ...
}
```

トレイトは、次の特徴をクラスに付与するでしょう。

- [find()](api:craft\db\SoftDeleteTrait::find()) はソフトデリートされていない（`dateDeleted` カラムが `null` である）行のみを返します。
- static な [findWithTrashed()](api:craft\db\SoftDeleteTrait::findWithTrashed()) メソッドは、ソフトデリートされたかどうかに関わらず行を見つけるために追加されます。
- static な [findTrashed()](api:craft\db\SoftDeleteTrait::findTrashed()) メソッドは、ソフトデリートされた（`dateDeleted` カラムが `null` ではない）行を見つけるために追加されます。
- `softDelete()` メソッドは、[delete()](api:yii\db\ActiveRecord::delete()) の代わりに呼び出すために追加され、行を削除するのではなく行の `dateDeleted` カラムを現在のタイムスタンプで更新します。
- `restore()` メソッドは、`dateDeleted` 値を削除することによってソフトデリート行を復元するために追加されます。

内部的には、トレイトは[ビヘイビア](https://www.yiiframework.com/doc/guide/2.0/en/concept-behaviors)として実装されている Yii 2 の [ActiveRecord Soft Delete Extension](https://github.com/yii2tech/ar-softdelete) を使用します。

クラスがすでに独自のビヘイビアを定義している場合、インポート時にトレイトの [behaviors()](api:craft\db\SoftDeleteTrait::behaviors()) メソッドをリネームし、あなたの `behaviors()` メソッドから手動で呼び出す必要があります。

```php
use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

class MyRecord extends ActiveRecord
{
    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }

    public function behaviors()
    {
        $behaviors = $this->softDeleteBehaviors();
        $behaviors['myBehavior'] = MyBehavior::class;
        return $behaviors;
    }

    // ...
}
```

クラスが <api:yii\db\ActiveRecord::find()> を上書きしている場合、結果のクエリに `dateDeleted` 条件を追加する必要があります。

```php{5}
public static function find()
{
    // @var MyActiveQuery $query
    $query = Craft::createObject(MyActiveQuery::class, [static::class]);
    $query->where(['dateDeleted' => null]);
    return $query;
}
```

## その他のコードの更新

コンポーネントのテーブルを含む、データベースクエリのコードを確認してください。それらも更新する必要があるでしょう。

- テーブルからデータを選択するときは、`dateDeleted` 値がある行を無視していることを確認してください。

   ```php{4}
   $results = (new \craft\db\Query())
    ->select(['...'])
    ->from(['{{%tableName}}'])
    ->where(['dateDeleted' => null])
    ->all();
   ```

- Active Record クラスを使用してテーブルから行を削除する場合、[delete()](api:yii\db\ActiveRecord::delete()) ではなく、新しい `softDelete()` メソッドを呼び出してください。

   ```php
   $record->softDelete();
   ```

- クエリコマンドを使用してテーブルから行を削除する場合、[delete()](api:yii\db\Command::delete()) ではなく、<api:craft\db\Command::softDelete()> を呼び出してください。

   ```php
   \Craft::$app->db->createCommand()
    ->softDelete('{{%tablename}}', ['id' => $id])
    ->execute();
   ```

## ソフトデリートされた行の復元

ガベージコレクションによってまだ完全に削除されていない、ソフトデリートされた行を復元するには2つの方法があります。

- Active Record クラスで `restore()` メソッドを呼び出す。

   ```php
   $record = MyRecord::findTrashed()
    ->where(['id' => $id])
    ->one();

   $record->restore();
   ```

- クエリコマンドで <api:craft\db\Command::restore()> を呼び出す。

   ```php
   \Craft::$app->db->createCommand()
    ->restore('{{%tablename}}', ['id' => $id])
    ->execute();
   ```

