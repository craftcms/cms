# プロジェクトコンフィグのサポート

あなたのプラグインがメインの[プラグイン設定](plugin-settings.md)以外の設定を保存するコンポーネントを持つ場合、[プロジェクトコンフィグ](../project-config.md)のサポートに適しているかもしれません。

## プロジェクトコンフィグのサポートがあなたに向いているか？

コンポーネントにプロジェクトコンフィグのサポートを追加する前に、トレードオフについて考慮してください。プロジェクトコンフィグによって管理されるコンポーネントは、開発環境の管理者だけが編集可能[であるべき](../project-config.md#production-changes-may-be-forgotten)です。

自身に問いかけてください。

- このコンポーネントは管理者以外でも管理できるのか？
- 管理者以外が管理可能な何かに依存していないか？
- コンポーネントが開発環境でしか編集できない場合、管理者のワークフローにとって扱いにくくならないか？

いずれかの答えが（現在、または、近い将来において）「はい」の場合、プロジェクトコンフィグのサポートに向いていません。

## プロジェクトコンフィグサポートの実装

プラグインにプロジェクトコンフィグのサポートを追加するには、サービスの CRUD コードを少し変える必要があります。

- CRUD メソッドは、データベース内のものを直接変更するのではなく、プロジェクトコンフィグを更新する必要があります。
- データベースの変更は、プロジェクトコンフィグの変更の結果によってのみ発生します。

Craft Commerce で製品タイプを保存するためにどのように機能するかを見てみましょう。

### ステップ 1：設定変更の感知

プラグインの `init()` メソッドで <api:craft\services\ProjectConfig::onAdd()>、[onUpdate()](api:craft\services\ProjectConfig::onUpdate())、および、[onRemove()](api:craft\services\ProjectConfig::onRemove()) を使用し、プロジェクトコンフィグから製品タイプが登録、更新、削除されたときに発動されるイベントリスナーを登録します。

```php
use craft\events\ConfigEvent;
use craft\services\ProjectConfig;

public function init()
{
    parent::init();

    Craft::$app->projectConfig
        ->onAdd('productTypes.{uid}', [$this->productTypes, 'handleChangedProductType'])
        ->onUpdate('productTypes.{uid}', [$this->productTypes, 'handleChangedProductType'])
        ->onRemove('productTypes.{uid}', [$this->productTypes, 'handleDeletedProductType']);
}
```

::: tip
`{uid}` は（[StringHelper::UUID()](api:craft\helpers\StringHelper::UUID()) で生成されたもののような）有効な UID にマッチする特別な設定パストークンです。イベントハンドラが呼び出されたときにパスが `{uid}` トークンを含んでいると、マッチする UID が [ConfigEvent::$tokenMatches](api:craft\events\ConfigEvent::$tokenMatches) 経由で利用可能になります。
:::

### ステップ 2：設定変更の操作

次に、config イベントリスナーで参照している `handleChangedProductType()`、および、`handleDeletedProductType()` メソッドを追加してください。

これらのファンクションは、プロジェクトコンフィグに基づいてデータベースを変更する責任があります。

```php
use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;

public function handleChangedProductType(ConfigEvent $event)
{
    // Get the UID that was matched in the config path
    $uid = $event->tokenMatches[0];
    
    // Does this product type exist?
    $id = (new Query())
        ->select(['id'])
        ->from('{{%producttypes}}')
        ->where(['uid' => $uid])
        ->scalar();

    $isNew = empty($id);

    // Insert or update its row
    if ($isNew) {
        Craft::$app->db->createCommand()
            ->insert('{{%producttypes}}', [
                'name' => $event->newValue['name'],
                // ...
            ])
            ->execute();
    } else {
        Craft::$app->db->createCommand()
            ->update('{{%producttypes}}', [
                'name' => $event->newValue['name'],
                // ...
            ], ['id' => $id])
            ->execute();
    }

    // Fire an 'afterSaveProductType' event?
    if ($this->hasEventHandlers('afterSaveProductType')) {
        $productType = $this->getProductTypeByUid($uid);
        $this->trigger('afterSaveProductType', new ProducTypeEvent([
            'productType' => $productType,
            'isNew' => $isNew,
        ]));
    }
}

public function handleDeletedProductType(ConfigEvent $event)
{
    // Get the UID that was matched in the config path
    $uid = $event->tokenMatches[0];

    // Get the product type
    $productType = $this->getProductTypeByUid($uid);

    // If that came back empty, we're done!
    if (!$productType) {
        return;
    }

    // Fire a 'beforeApplyProductTypeDelete' event?
    if ($this->hasEventHandlers('beforeApplyProductTypeDelete')) {
        $this->trigger('beforeApplyProductTypeDelete', new ProducTypeEvent([
            'productType' => $productType,
        ]));
    }

    // Delete its row
    Craft::$app->db->createCommand()
        ->delete('{{%producttypes}}', ['id' => $productType->id])
        ->execute();

    // Fire an 'afterDeleteProductType' event?
    if ($this->hasEventHandlers('afterDeleteProductType')) {
        $this->trigger('afterDeleteProductType', new ProducTypeEvent([
            'productType' => $productType,
        ]));
    }
}
```

この時点で、プロジェクトコンフィグに手動で製品タイプを追加または削除した場合、それらの変更はデータベースに同期され、イベントトリガーの `afterSaveProductType`、`beforeApplyProductTypeDelete`、および、`afterDeleteProductType` が発動します。

::: tip
あなたのコンポーネント設定が他のコンポーネント設定を参照している場合、ハンドラーメソッド内で [ProjectConfig::processConfigChanges()](api:craft\services\ProjectConfig::processConfigChanges()) を呼び出すことで、他の設定変更が最初に処理されることが保証されます。

```php
Craft::$app->projectConfig->processConfigChanges('productTypeGroups');
```

:::

### ステップ 3：設定への変更のプッシュ

あとは、データベースの更新ではなくプロジェクトコンフィグを更新するよう、サービスメソッドをアップデートするだけです。

プロジェクトコンフィグの項目は、<api:craft\services\ProjectConfig::set()> を使用して追加または更新したり、[remove()](api:craft\services\ProjectConfig::remove()) を使用して削除できます。

```php
use Craft;
use craft\helpers\Db;
use craft\helpers\StringHelper;

public function saveProductType($productType)
{
    $isNew = empty($productType->id);

    // Ensure the product type has a UID
    if ($isNew) {
        $productType->uid = StringHelper::UUID();
    } else if (!$productType->uid) {
        $productType->uid = Db::uidById('{{%producttypes}}', $productType->id);
    }

    // Fire a 'beforeSaveProductType' event?
    if ($this->hasEventHandlers('beforeSaveProductType')) {
        $this->trigger('beforeSaveProductType', new ProducTypeEvent([
            'productType' => $productType,
            'isNew' => $isNew,
        ]));
    }

    // Make sure it validates
    if (!$productType->validate()) {
        return false;
    }

    // Save it to the project config
    $path = "product-types.{$productType->uid}";
    Craft::$app->projectConfig->set($path, [
        'name' => $productType->name,
        // ...
    ]);

    // Now set the ID on the product type in case the
    // caller needs to know it
    if ($isNew) {
        $productType->id = Db::idByUid('{{%producttypes}}', $productType->uid);
    }

    return true;
}

public function deleteProductType($productType)
{
    // Fire a 'beforeDeleteProductType' event?
    if ($this->hasEventHandlers('beforeDeleteProductType')) {
        $this->trigger('beforeDeleteProductType', new ProducTypeEvent([
            'productType' => $productType,
        ]));
    }

    // Remove it from the project config
    $path = "product-types.{$productType->uid}";
    Craft::$app->projectConfig->remove($path);
}
```

## プロジェクトコンフィグのマイグレーション

プラグインの[マイグレーション](migrations.md)から、プロジェクトコンフィグの項目を追加、更新、削除することができます。ただし、データベースを変更するよりも少し複雑です。というのも、異なる環境の同じ設定に対し同じマイグレーションがすでに実行されている可能性があるためです。

次のシナリオを考慮してください。

1. プロジェクトコンフィグを変更する新しいマイグレーションが含まれるプラグインが、環境 A でアップデートされました。
3. 更新された `composer.lock`、および、`project.yaml` が Git にコミットされました。
4. Craft が新しいプラグインのマイグレーションを実行し、_さらに_、`project.yaml` の変更を同期しなければならない環境 B に、その Git のコミットがプルされました。

新しいプラグインのマイグレーションが保留中、_かつ_、 `project.yaml` の変更が保留中の場合、Craft は最初にマイグレーションを実行してから `project.yaml` の変更を同期します。

プラグインのマイグレーションで環境 B に対して同じプロジェクトコンフィグの変更が行われる場合、`project.yaml` で保留中の変更と競合します。

これを避けるために、プロジェクトコンフィグを変更する前に必ず _`project.yaml` の_プラグインのスキーマバージョンを確認してください。（[ProjectConfig::get()](api:craft\services\ProjectConfig::get()) を呼び出す際の第2引数に `true`を渡すことによって、それを行います。）

```php
public function safeUp()
{
    // Don't make the same config changes twice
    $schemaVersion = Craft::$app->projectConfig
        ->get('plugins.<plugin-handle>.schemaVersion', true);

    if (version_compare($schemaVersion, '<NewSchemaVersion>', '<')) {
        // Make the config changes here...
    }
}
```

## プロジェクトコンフィグデータの再構築

プラグインがプロジェクトコンフィグとデータベースの両方にデータを保存している場合、`./craft project-config/rebuild` コマンドが実行されたときに Craft がデータベースに保存されたデータに基づいてプロジェクトコンフィグを再構築するのを支援するよう（Craft 3.1.20 で追加された）<api:craft\services\ProjectConfig::EVENT_REBUILD> を感知してください。

```php
use craft\events\RebuildConfigEvent;
use craft\services\ProjectConfig;
use yii\base\Event;

Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $e) {
    // Add plugin's project config data...
   $e->config['myPlugin']['key'] = $value;
});
```

