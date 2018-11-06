# サービス

[[toc]]

## サービスとは？

サービスは、[コンポーネント](https://www.yiiframework.com/doc/guide/2.0/en/structure-application-components)のようにプライマリプラグインクラスに付加される（例：`MyPlugin::getInstance()->serviceName`）[singleton](https://en.wikipedia.org/wiki/Singleton_pattern) クラスです。

それは2つの働きを持っています。

- プラグインのビジネスロジックの大半を含んでいます。
- プラグイン（および、他のプラグイン）がアクセスできる、プラグインの API を定義します。

例えば、Craft のフィールド管理コードは <api:craft\services\Fields> にあり、`Craft::$app->fields` で利用できます。それは、ハンドルによってフィールドモデルを返す `getFieldByHandle()` メソッドを持ちます。そうしたい場合は、`Craft::$app->fields->getFieldByHandle('foo')` で呼び出すことができます。

## サービスの作成

プラグインのサービスクラスを作成するために、プラグインの `src/` ディレクトリ内に `services/` サブディレクトリを作成し、提供したいサービスのクラス名にちなんで名付けられたファイルを作成します。サービスクラスの名前を `Foo` にしたい場合、ファイルに `Foo.php` という名前をつけます。

テキストエディタでファイルを開き、出発点としてこのテンプレートを使用してください。

```php
<?php
namespace ns\prefix\services;

use yii\base\Component;

class Foo extends Component
{
    // ...
}
```

サービスクラスが存在すると、[init()](api:yii\base\BaseObject::init()) メソッドから [setComponents()](api:yii\di\ServiceLocator::setComponents()) を呼び出すことによって、プライマリプラグインクラスのコンポーネントとして登録できます。

```php
public function init()
{
    parent::init();

    $this->setComponents([
        'foo' => \ns\prefix\services\Foo::class,
    ]);

    // ...
}
```

## サービスメソッドの呼び出し

`MyPlugin::getInstance()->serviceName` を使用して、コードベースのどこからでもサービスにアクセスできます。そのため、サービス名が `foo` で `bar()` と名付けられたメソッドを持つ場合、次のように呼び出すことができます。

```php
MyPlugin::getInstance()->foo->bar()
```

プライマリプラグインクラスから直接サービスメソッドを呼び出す必要がある場合、 `MyPlugin::getInstance()` をスキップして `$this` を使用できます。

```php
$this->foo->bar()
```

## モデル操作メソッド

多くのサービスメソッドは、CRUD 操作のような特定のモデルに向けてある種の操作を行います。

Craft には、2つの一般的なモデル操作メソッドがあります。

1. *特定のモデルクラス*（例：指定された <api:craft\models\CategoryGroup> モデルによって表されるカテゴリグループを保存する <api:craft\services\Categories::saveGroup()>）を受け入れるメソッド。私たちは、これらを**クラス指向メソッド**と呼びます。

2. *インターフェース*（例：実際のクラスかどうかに関わらず、指定された <api:craft\base\FieldInterface> インターフェースで表されるフィールドを削除する <api:craft\services\Fields::deleteField()>）を実装している限り、すべてのクラスを受け入れるメソッド。私たちは、これらを**インターフェース指向メソッド**と呼びます。

両方のタイプのメソッドは、1つの違いを除き同じ一般的な制御フローに従う必要があります。インターフェース指向メソッドは、アクションが実行される前後にモデルのコールバックメソッドをトリガし、モデルに独自のカスタムロジックを実行するチャンスを与えるべきです。

ここに例を示します。<api:craft\services\Elements::saveElement()> は、`elements` データベーステーブルにエレメントのレコードを保存する前後で、エレメントモデルの `beforeSave()` および `afterSave()` メソッドを呼び出します。 エントリエレメント（<api:craft\elements\Entry>）は、エントリ特有の `entries` データベーステーブルに行を保存する機会として、`afterSave()` メソッドを使用します。

### クラス指向メソッド

クラス指向メソッドの制御フロー図です。

```
╔════════════════════════════╗
║ saveRecipe(Recipe $recipe) ║
╚════════════════════════════╝
               │
               ▼
  ┌────────────────────────┐
  │ beforeSaveRecipe event │
  └────────────────────────┘
               │
               ▼
               Λ
              ╱ ╲
             ╱   ╲              ┏━━━━━━━━━━━━━━┓
          validates? ─── no ───▶┃ return false ┃
             ╲   ╱              ┗━━━━━━━━━━━━━━┛
              ╲ ╱
               V
               │
              yes
               │
               ▼
     ┌───────────────────┐
     │ begin transaction │
     │      (maybe)      │
     └───────────────────┘
               │
               ▼
      ┌─────────────────┐
      │ save the recipe │
      └─────────────────┘
               │
               ▼
      ┌─────────────────┐
      │ end transaction │
      │     (maybe)     │
      └─────────────────┘
               │
               ▼
   ┌───────────────────────┐
   │ afterSaveRecipe event │
   └───────────────────────┘
               │
               ▼
        ┏━━━━━━━━━━━━━┓
        ┃ return true ┃
        ┗━━━━━━━━━━━━━┛
```

::: tip
操作が複数データベースの変更を含む場合、データベーストランザクションで操作をラップする必要があるだけです。
:::

完全なコードの実例は、次のようになります。

```php
public function saveRecipe(Recipe $recipe, $runValidation = true)
{
    // Fire a 'beforeSaveRecipe' event
    $this->trigger(self::EVENT_BEFORE_SAVE_RECIPE, new RecipeEvent([
        'recipe' => $recipe,
        'isNew' => $isNewRecipe,
    ]));

    if ($runValidation && !$recipe->validate()) {
        \Craft::info('Recipe not saved due to validation error.', __METHOD__);
        return false;
    }

    $isNewRecipe = !$recipe->id;

    // ... Save the recipe here ...

    // Fire an 'afterSaveRecipe' event
    $this->trigger(self::EVENT_AFTER_SAVE_RECIPE, new RecipeEvent([
        'recipe' => $recipe,
        'isNew' => $isNewRecipe,
    ]));

    return true;
}
```

### インターフェース指向メソッド

インターフェース指向メソッドの制御フロー図です。

```
╔═════════════════════════════════════════════════╗
║ saveIngredient(IngredientInterface $ingredient) ║
╚═════════════════════════════════════════════════╝
                         │
                         ▼
          ┌────────────────────────────┐
          │ beforeSaveIngredient event │
          └────────────────────────────┘
                         │
                         ▼
                         Λ
                        ╱ ╲
                       ╱   ╲                        ┏━━━━━━━━━━━━━━┓
             $ingredient->beforeSave() ── false ───▶┃ return false ┃
                       ╲   ╱                        ┗━━━━━━━━━━━━━━┛
                        ╲ ╱
                         V
                         │
                        true
                         │
                         ▼
                         Λ
                        ╱ ╲
                       ╱   ╲              ┏━━━━━━━━━━━━━━┓
                    validates? ─── no ───▶┃ return false ┃
                       ╲   ╱              ┗━━━━━━━━━━━━━━┛
                        ╲ ╱
                         V
                         │
                        yes
                         │
                         ▼
               ┌───────────────────┐
               │ begin transaction │
               └───────────────────┘
                         │
                         ▼
              ┌─────────────────────┐
              │ save the ingredient │
              └─────────────────────┘
                         │
                         ▼
           ┌──────────────────────────┐
           │ $ingredient->afterSave() │
           └──────────────────────────┘
                         │
                         ▼
                ┌─────────────────┐
                │ end transaction │
                └─────────────────┘
                         │
                         ▼
           ┌───────────────────────────┐
           │ afterSaveIngredient event │
           └───────────────────────────┘
                         │
                         ▼
                  ┏━━━━━━━━━━━━━┓
                  ┃ return true ┃
                  ┗━━━━━━━━━━━━━┛
```

完全なコードの実例は、次のようになります。

```php
public function saveIngredient(IngredientInterface $ingredient, $runValidation = true)
{
    /** @var Ingredient $ingredient */

    // Fire a 'beforeSaveIngredient' event
    $this->trigger(self::EVENT_BEFORE_SAVE_INGREDIENT, new IngredientEvent([
        'ingredient' => $ingredient,
        'isNew' => $isNewIngredient,
    ]));

    if (!$ingredient->beforeSave()) {
        return false;
    }

    if ($runValidation && !$ingredient->validate()) {
        \Craft::info('Ingredient not saved due to validation error.', __METHOD__);
        return false;
    }

    $isNewIngredient = !$ingredient->id;

    $transaction = \Craft::$app->getDb()->beginTransaction();
    try {
        // ... Save the ingredient here ...

        $ingredient->afterSave();

        $transaction->commit();
    } catch (\Exception $e) {
        $transaction->rollBack();
        throw $e;
    }

    // Fire an 'afterSaveIngredient' event
    $this->trigger(self::EVENT_AFTER_SAVE_INGREDIENT, new IngredientEvent([
        'ingredient' => $ingredient,
        'isNew' => $isNewIngredient,
    ]));

    return true;
}
```

