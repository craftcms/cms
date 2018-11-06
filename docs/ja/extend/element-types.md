# エレメントタイプ

エレメントタイプは、Craft で管理できる様々なタイプのコンテンツを定義します。

Craft には、7つの組み込みフィールドタイプがあります。

- <api:craft\elements\Asset>
- <api:craft\elements\Category>
- <api:craft\elements\Entry>
- <api:craft\elements\GlobalSet>
- <api:craft\elements\MatrixBlock>
- <api:craft\elements\Tag>
- <api:craft\elements\User>

これらのクラスの実例を参照できます。それらは `vendor/craftcms/cms/src/elements/` にあります。

プラグインが新しいコンテンツタイプを提供する必要がある場合、エレメントタイプとして設計することが通常は最善の方法です。

[[toc]]

## はじめよう

### エレメントクラス

エレメントタイプは、<api:craft\base\ElementInterface> と <api:craft\base\ElementTrait> を実装するクラスによって定義されます。クラスは、（静的メソッドで）エレメントタイプについての様々なことと伝える手段としてだけでなく、その型のエレメントがインスタンス化されるモデルとしても役立ちます。

便利なものとして、基本エレメントタイプの実装を提供する <api:craft\base\Element> を拡張できます。

プラグインのソースディレクトリ内で `elements/` ディレクトリを作成し、その中に提供したいエレメントタイプのクラス名にちなんで名付けられた PHP クラスファイルを作成します（例：`Product.php`）。

そのファイル内にクラスを定義し、エレメントに持たせたいカスタム属性のためのパブリックプロパティを与えます。

```php
<?php
namespace ns\prefix\elements;

use craft\base\Element;

class Product extends Element
{
    /**
     * @var int Price
     */
    public $price = 0;

    /**
     * @var string Currency code
     */
    public $currency;

    // ...
}
```

### データベーステーブル

`elements` データベーステーブルのカラムにフィットしない、エレメント自身について保存したいことがあるでしょう。そこで、その情報を保持するための新しいテーブルを作成する必要があります。

（まだ用意していない場合）[インストールマイグレーション](plugin-migrations.md#install-migrations)を作成し、 `safeUp()` メソッドにこれを追加してください。

```php
if (!$this->db->tableExists('{{%products}}')) {
    // create the products table
    $this->createTable('{{%products}}', [
        'id' => $this->integer()->notNull(),
        'price' => $this->integer()->notNull(),
        'currency' => $this->char(3)->notNull(),
        'dateCreated' => $this->dateTime()->notNull(),
        'dateUpdated' => $this->dateTime()->notNull(),
        'uid' => $this->uid(),
        'PRIMARY KEY(id)',
    ]);

    // give it a FK to the elements table
    $this->addForeignKey(
        $this->db->getForeignKeyName('{{%products}}', 'id'),
        '{{%products}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
}
```

::: tip
既存のプラグインのアップデートとしてこれを追加する場合、同様に新しい通常のマイグレーションを作成し、同じコードをその中にコピーする必要があります。
:::

プラグインをインストールすると、データベーステーブルが作成されるでしょう。

エレメントが保存された際、エレメントテーブルをアップデートし続ける責任があるエレメントクラスに `afterSave()` メソッドを追加する必要もあります。`afterSave()` メソッドは、エレメントを保存する標準的な[制御フロー](services.md#interface-oriented-methods)の一部です。

```php
public function afterSave(bool $isNew)
{
    if ($isNew) {
        \Craft::$app->db->createCommand()
            ->insert('{{%products}}', [
                'id' => $this->id,
                'price' => $this->price,
                'currency' => $this->currency,
            ])
            ->execute();
    } else {
        \Craft::$app->db->createCommand()
            ->update('{{%products}}', [
                'price' => $this->price,
                'currency' => $this->currency,
            ], ['id' => $this->id])
            ->execute();
    }

    parent::afterSave($isNew);
}
```

### エレメントクエリクラス

すべてのエレメントタイプは、対応するエレメントクエリクラスが必要です。エレメントクエリクラスは、エレメントを取得するためにチューニングされた[クエリビルダー](https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder)の拡張です。

すべてのエレメントクエリクラスは、基本機能を提供する <api:craft\elements\db\ElementQuery> を拡張する必要があります。

それらには、3つの責任があります。

- カスタム基準パラメータをキャプチャするためのパブリックプロパティと Setter メソッドを提供します
- カスタムエレメントテーブルに結合し、その中にある適切なカラムを選択します
- カスタム基準パラメータをクエリの条件として適用します

実例として、Craft 独自のエレメントクラスを参照できます。それらは `vendor/craftcms/cms/src/elements/db/` にあります。

プラグインにエレメントクエリを渡すために、`elements/` ディレクトリ内に `db/` ディレクトリを作成し、提供したいエレメントクエリにちなんで名付けられた PHP クラスファイルを作成します（例：`ProductQuery.php`）。

```php
<?php
namespace ns\prefix\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use ns\prefix\elements\Product;

class ProductQuery extends ElementQuery
{
    public $price;
    public $currency;

    public function price($value)
    {
        $this->price = $value;

        return $this;
    }

    public function currency($value)
    {
        $this->currency = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the products table
        $this->joinElementTable('products');

        // select the price column
        $this->query->select([
            'products.price',
            'products.currency',
        ]);

        if ($this->price) {
            $this->subQuery->andWhere(Db::parseParam('products.price', $this->price));
        }

        if ($this->currency) {
            $this->subQuery->andWhere(Db::parseParam('products.currency', $this->currency));
        }

        return parent::beforePrepare();
    }
}
```

エレメントクエリクラスが配置されていれば、最後のステップはエレメントタイプに結びつけることです。エレメントクラスに次のメソッドを追加してください。

```php
use craft\elements\db\ElementQueryInterface;
use ns\prefix\elements\db\ProductQuery;

// ...

class Product
{
    public static function find(): ElementQueryInterface
    {
        return new ProductQuery(static::class);
    }

    // ...
}
```

あなたの型のエレメント向けに照会しはじめる準備ができました。

```php
Product::find()
    ->price(100)
    ->all();
```

#### `$this->query` 対 `$this->subQuery`

その裏で、<api:craft\elements\db\ElementQuery> は2つの <api:craft\db\Query> インスタンスを作成します。メインクエリ（`$this->query`）とサブクエリ（`$this->subQuery`）です。カラムの選択はメインクエリで行い、条件 / 結合はサブクエリに適用する必要があります。最終的に、サブクエリはメインクエリの `FROM` 句になります。

このように分かれている理由は、パフォーマンスです。一時テーブルでコストの高い条件操作を実行する必要を避け、どのカラムを選択するかなどを気にすることなく、どのエレメント行を取得するかを MySQL / PostgreSQL が正確に把握できるようにします。

### テンプレートファンクション

テンプレートでエレメントを照会できるようにする場合、新しいエレメントクエリを返す新しいテンプレートファンクションを作成できます。（詳細については、[Twig の拡張](extending-twig.md) を参照してください。）

```php
<?php
namespace ns\prefix;

use Craft;
use yii\base\Behavior;

/**
 * Adds a `craft.products()` function to the templates (like `craft.entries()`) 
 */
class CraftVariableBehavior extends Behavior
{
    public function products($criteria = null): ProductQuery
    {
        $query = Product::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }
        return $query;
    }
}
```

## エレメントコンテンツ

エレメントが `content` テーブル内の独自の行を取得する必要がある場合、[タイトル](#titles)や[コンテンツフィールド](#custom-fields)を持つ必要があるため、エレメントクラスに static な `hasContent()` メソッドを追加します。

```php
public static function hasContent(): bool
{
    return true;
}
```

### タイトル

エレメントがユーザー定義のタイトルを持つ場合、エレメントクラスに static な `hasTitles()` メソッドを追加します。

```php
public static function hasTitles(): bool
{
    return true;
}
```

[エレメントエディタの HUD](#editor-huds) はタイトルフィールドを自動的に表示しないため、自身で追加する必要があることに注意してください。

```php
public function getEditorHtml(): string
{
    $html = \Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
        [
            'label' => \Craft::t('app', 'Title'),
            'siteId' => $this->siteId,
            'id' => 'title',
            'name' => 'title',
            'value' => $this->title,
            'errors' => $this->getErrors('title'),
            'first' => true,
            'autofocus' => true,
            'required' => true
        ]
    ]);

    // ...

    $html .= parent::getEditorHtml();

    return $html;
}
```

### カスタムフィールド

#### フィールドレイアウトの管理

エレメントタイプでカスタムフィールドをサポートする場合、エレメントタイプのフィールドレイアウトを管理するためにコントロールパネル内のどこかにページを作成する必要があります。Craft は Field Layout Designer を出力するテンプレートのインクルードを提供します。

```twig
{% include "_includes/fieldlayoutdesigner" with {
    fieldLayout: craft.app.fields.getLayoutByType('ns\\prefix\\elements\\Product')
} only %}
```

プラグインのコントローラーの1つの投稿のための `<form>` 内に include を配置してください。コントローラーは、次のようにフィールドレイアウトを保存できます。

```php
use ns\prefix\elements\Product;

// ...

// assemble the new one from the post data, and save it
$fieldLayout = \Craft::$app->getFields()->assembleLayoutFromPost();
$fieldLayout->type = Product::class;
\Craft::$app->getFields()->saveLayout($fieldLayout);
```

エレメントタイプ全体で1つのフィールドレイアウトを持つのではなく、必要であれば複数のフィールドレイアウトを管理することもできます。例えば、エントリのフィールドレイアウトはそれぞれの入力タイプ向けに定義され、アセットのフィールドレイアウトはそれぞれのアセットボリューム向けに定義されます。

あなたがしたいように、セットすることができます。データベースのどこかに、新しいフィールドレイアウトの ID を保存することを忘れないでください。（`$fieldLayout->id` 経由で `saveLayout()` を呼び出した後、フィールドレイアウトの ID にアクセスできます。)

#### フィールドレイアウトへのエレメントの関連付け

エレメントの `getFieldLayout()` メソッドは（存在する場合）現在のエレメントに関連付けられたフィールドレイアウトを返す責任があります。デフォルトでは、エレメントの `$fieldLayoutId` プロパティをチェックします。セットされている場合、同じ ID のフィールドレイアウトを返します。そのため、それらを保存する際、エレメントに `$fieldLayoutId` プロパティをセットすることを推奨します。

```php
// ...
$product->fieldLayoutId = $productType->fieldLayoutId;
\Craft::$app->elements->saveElement($product);
```

`$fieldLayoutId` プロパティがセットされている場合、<api:craft\services\Elements::saveElement()> はデータベースの `elements.fieldLayoutId` カラムに保存し、ロード時に取得されたその値をエレメントに再設定します。

あるいは、`getFieldLayout()` メソッドを上書きし、フィールドレイアウトを fetch / return することもできます。これは（ユーザーアカウントのように）エレメントタイプが単一のフィールドレイアウトしか持たない場合、むしろ望ましいかもしれません。

```php
public function getFieldLayout()
{
    return \Craft::$app->fields->getLayoutByType(Product::class);
}
```

### ローカライゼーション

エレメントのタイトルやカスタムフィールドの値がサイト単位で保存されている場合、static な `isLocalized()` メソッドを追加してください。

```php
public static function isLocalized(): bool
{
    return true;
}
```

デフォルトでは、エレメントはすべてのサイトに保存されます。特定のサイトだけにエレメントを保存する必要がある場合、`getSupportedSites()` メソッドを追加してください。

```php
public function getSupportedSites(): array
{
    return [
        1,
        2,
        ['siteId' => 3, 'enabledByDefault' => false],
    ];
}
```

`getSupportedSites()` によって返される配列内の値は、整数値（サイト ID）、または、`siteId` キーとそのサイトでエレメントがデフィルトで使用可能であるべきかを示すオプションの `enabledbyDefault` キー（ブール値）の配列のいずれかにできます。

## ステータス

エレメントに独自のステータスが必要な場合、エレメントクラスに static な `hasStatuses()` メソッドを加えます。

```php
public static function hasStatuses(): bool
{
    return true;
}
```

次に、`enabled` と `disabled` 以外のステータスを持つことができる場合、それらを定義するために static な `statuses()` メソッドを追加してください。

```php
public static function statuses(): array
{
    return [
        'foo' => \Craft::t('plugin-handle', 'Foo'),
        'bar' => \Craft::t('plugin-handle', 'Bar'),
    ];
}
```

## ソース

エレメントタイプは、基準パラメータで定義されたエレメントのグループである「ソース」を定義できます。

エレメントタイプのソースは、エレメントインデックスのサイドバーとエレメントの関連フィールドの設定内に表示されます。

エレメントタイプのソースを定義するために、エレメントクラスに protected static な `defineSources()` メソッドを追加してください。

```php
protected static function defineSources(string $context = null): array
{
    return [
        [
            'key' => '*',
            'label' => 'All Products',
            'criteria' => []
        ],
        [
            'key' => 'cad',
            'label' => 'CAD',
            'criteria' => [
                'currency' => 'cad',
            ]
        ],
        [
            'key' => 'usd',
            'label' => 'USD',
            'criteria' => [
                'currency' => 'usd',
            ]
        ],
    ];
}
```

ソースが選択されると、Craft はソースの `criteria` 配列にリストされている値で[エレメントクエリ](#element-query-class)を設定します。

## インデックスページ

次のテンプレートを使用して、[コントロールパネルのセクション](cp-section.md)にエレメントタイプのインデックスページを加えることができます。

```twig
{% extends '_layouts/elementindex' %}
{% set title = 'Products' %}
{% set elementType = 'ns\\prefix\\elements\\Product' %}
```

### インデックスページアクション

エレメントクラスに protected static な `defineActions()`メソッドを追加することで、インデックスページでエレメントタイプをサポートする[アクション](element-action-types.md)を定義できます。

```php
protected static function defineActions(string $source = null): array
{
    return [
        FooAction::class,
        BarAction::class,
    ];
}
```

### ソートオプション

エレメントクラスに protected static な `defineSortOptions()` メソッドを追加することで、エレメントインデックス向けのソートオプションを定義できます。

```php
protected static function defineSortOptions(): array
{
    return [
        'title' => \Craft::t('app', 'Title'),
        'price' => \Craft::t('plugin-handle', 'Price'),
    ];
}
```

ソートオプションがインデックスで選択されると、キーが[エレメントクエリ](#element-query-class)クラスの `$orderBy` プロパティに渡されます（例：`['price' => SORT_ASC]`）。

### テーブル属性

エレメントクラスに protected な `defineTableAttributes()` メソッドを追加することで、エレメントインデックスのテーブルビューで利用可能な列をカスタマイズできます。

```php
protected static function defineTableAttributes(): array
{
    return [
        'title' => \Craft::t('app', 'Title'),
        'price' => \Craft::t('plugin-handle', 'Price'),
        'currency' => \Craft::t('plugin-handle', 'Currency'),
    ];
}
```

::: tip
ここでリストする最初の属性は、特別なケースです。唯一管理者が削除できない、テーブルビューの最初の列のヘッダーを定義します。その値は（`__toString()` メソッドが返す）エレメントの文字列表現です。
:::

大きなリストの場合、エレメントクラスに protected な `defineDefaultTableAttributes()` メソッドを追加することで、新しい[ソース](#sources)向けのデフォルトで表示する列を制限することもできます。

```php
protected static function defineDefaultTableAttributes(string $source): array
{
    return ['title', 'price', 'currency'];
}
```

テーブルセル向けに、Craft はデフォルトでエレメント属性の文字列の型を出力します。エレメントクラスに protected な `tableAttributeHtml()` メソッドを追加することで、セルの HTML を上書きできます。

```php
protected function tableAttributeHtml(string $attribute): string
{
    switch ($attribute) {
        case 'price':
            return \Craft::$app->formatter->asCurrency($this->price, $this->currency);

        case 'currency':
            return strtoupper($this->currency);
    }

    return parent::tableAttributeHtml($attribute);
}
```

### サムネイルビュー

サムネイルビューは、[ソース](#sources)単位でエレメントインデックスページ向けに有効にすることができます。

ソースのサムネイルビューを有効にするには、その定義に `hasThumbs` キーを追加してください。

```php
protected static function defineSources(string $context = null): array
{
    return [
        [
            'key' => 'cad',
            'label' => 'CAD',
            'criteria' => [
                'currency' => 'cad',
            ],
            'hasThumbs' => true
        ],
        // ...
    ];
}
```

次に、エレメントクラスに現在のエレメントのサムネイルの URL を返す `getThumbUrl()` メソッドを追加してください。

```php
use craft\helpers\UrlHelper;

// ...

public function getThumbUrl(int $size)
{
    return UrlHelper::resourceUrl("product-images/{$this->id}/{$size}");
}
```

## 検索可能な属性

エレメントが保存されると、Craft の検索サービスはそのエレメントの検索キーワードとして「検索可能な属性」をインデックスします。デフォルトでは、検索可能な属性のリストにはエレメントのタイトルとスラグ、および、カスタムフィールドの値のみが含まれます。

エレメントタイプに検索可能な属性を追加したい場合、エレメントに protected static な `defineSearchableAttributes()` メソッドを追加し、それらをリストに入れてください。

```php
protected static function defineSearchableAttributes(): array
{
    return ['price'];
}
```

## エレメント URL

エレメントが保存される際、エレメントがシステム内で独自の URI を持ち、存在する場合はどのような見た目になるかを探すために `getUriFormat()` メソッドが呼び出されます。

そのため、エレメントの独自の URL を得る場合、このメソッドを実装し、<api:craft\web\View::renderObjectTemplate()> で解析できる文字列（例：`products/{slug}`）を返さなければなりません。通常、これはハードコードされたものではなく、ユーザー定義の文字列である必要があります。

```php
public function getUriFormat()
{
    return $this->getType()->uriFormat;
}
```

エレメントの URL がリクエストされるたびに、Craft はエレメントをインスタンス化し、その `getRoute()` メソッドを呼び出し、リクエストがどのように[ルーティングされる](https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing)べきか、エレメントに決定するチャンスを与えます。

内部的には、<api:craft\base\Element::getRoute()> はエレメントクラスで上書きしたい protected な `route()` メソッドを呼び出します。

```php
protected function route()
{
    return [
        'templates/render', [
            'template' => $this->getType()->template,
            'variables' => [
                'product' => $this,
            ]
        ]
    ];
}
```

## エレメントの編集

### エディタの HUD

インデックスページや関連フィールド内でダブルクリックした際、エレメントエディタの HUD 経由でエレメントを編集できるようにするには、エレメントクラスに現在のユーザーがエレメントを編集する権限を持っているかどうかを返す `getIsEditable()` メソッドを追加してください。

```php
public function getIsEditable(): bool
{
    return \Craft::$app->user->checkPermission('edit-product:'.$this->getType()->id);
}
```

デフォルトでは、エレメントエディターの HUD はカスタムフィールドだけが含まれます。タイトルフィールド、および / または、エレメント固有の属性フィールドを含めるには、エレメントクラスに `getEditorHtml()` メソッドを追加してください。

```php
public function getEditorHtml(): string
{
    $html = \Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
        [
            'label' => \Craft::t('app', 'Title'),
            'siteId' => $this->siteId,
            'id' => 'title',
            'name' => 'title',
            'value' => $this->title,
            'errors' => $this->getErrors('title'),
            'first' => true,
            'autofocus' => true,
            'required' => true
        ]
    ]);

    $html .= parent::getEditorHtml();

    return $html;
}
```

### ページの編集

エレメントタイプにフルサイズの編集ページを与えたい場合、テンプレート、ルート、コントローラーアクションのすべてをセットする必要があります。

カテゴリの編集ページは、それを実行するための比較的簡単な実例を提供します。

- URL ルール：

   ```php
    'categories/<groupHandle:{handle}>/new' => 'categories/edit-category',
    'categories/<groupHandle:{handle}>/<categoryId:\d+><slug:(?:-{slug})?>' => 'categories/edit-category',
    'categories/<groupHandle:{handle}>/<categoryId:\d+><slug:(?:-{slug})?>/<siteHandle:{handle}>' => 'categories/edit-category',
    'categories/<groupHandle:{handle}>/new/<siteHandle:{handle}>' => 'categories/edit-category',
   ```

- コントローラーアクション：
   - [actionEditCategory()](api:craft\controllers\CategoriesController::actionEditCategory()) – カテゴリの編集ページをレンダリングします
   - [actionPreviewCategory()](api:craft\controllers\CategoriesController::actionPreviewCategory()) – ライブプレビューリクエストのカテゴリのフロントエンドページをレンダリングします
   - [actionSaveCategory()](api:craft\controllers\CategoriesController::actionSaveCategory()) – カテゴリを保存します
   - [actionDeleteCategory()](api:craft\controllers\CategoriesController::actionDeleteCategory()) – カテゴリを削除します
   - [actionShareCategory()](api:craft\controllers\CategoriesController::actionShareCategory()) – Share Category リクエストを操作し、`categories/view-shared-category` のトークンを作成し、ユーザーをそこにリダイレクトします
   - [actionViewSharedCategory()](api:craft\controllers\CategoriesController::actionViewSharedCategory()) – Share Category トークンのカテゴリのフロントエンドページをレンダリングします

- カテゴリの編集ページテンプレート： [categories/_edit.html](https://github.com/craftcms/cms/blob/develop/src/templates/categories/_edit.html)

エレメントの編集ページをセットアップしたら、エレメントクラスにコントロールパネル内でエレメントの編集ページ URL を伝える [getCpEditUrl()](api:craft\base\ElementInterface::getCpEditUrl()) メソッドを追加してください。

```php
public function getCpEditUrl()
{
    return 'plugin-handle/products/'.$this->id;
}
```

## リレーション

### リレーションフィールド

<api:craft\fields\BaseRelationField> を拡張する新しい[フィールドタイプ](field-types.md)を作成することで、エレメントに独自の関連フィールドを与えることができます。

その基本クラスは単純な作業のほとんどを行います。そのため、3つのシンプルなメソッドを実装することで、フィールドを稼働させることができます。

```php
<?php
namespace ns\prefix\fields;

use craft\fields\BaseRelationsField;
use ns\prefix\elements\Product;

class Products extends BaseRelationField
{
    public static function displayName(): string
    {
        return \Craft::t('plugin-handle', 'Products');
    }

    protected static function elementType(): string
    {
        return Product::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return \Craft::t('plugin-handle', 'Add a product');
    }
}
```

## リファレンスタグ

エレメントで参照タグ（例：`{product:100}`）をサポートする場合、エレメントクラスに参照タグに使用されるユニークなハンドルを返す static な `refHandle()` メソッドを追加してください。

```php
public static function refHandle()
{
    return 'product';
}
```

ユーザーがエレメントの参照タグを簡単にコピーできるようにするには、エレメントのインデックスページに「リファレンスタグのコピー」[アクション](#index-page-actions)を追加する必要があります。

```php
use craft\elements\actions\CopyReferenceTag;

// ...

protected static function defineActions(string $source = null): array
{
    return [
        [
            'type' => CopyReferenceTag::class,
            'elementType' => static::class,
        ],
        // ...
    ];
}
```

## Eager-Loading

エレメントタイプが独自の[関連フィールド](#relation-field)を持っているなら、それはすでに eager-loadable です。そして、[カスタムフィールド](#custom-fields)をサポートする場合、関連フィールドでエレメントに関連づけられているすべてのエレメントは eager-loadable になります。

eager-loading サポートが自由に提供されない唯一のケースは、エレメントタイプが他のエレメントと「ハードコーディングされた」関連付けを持っている場合です。例えば、エントリは著者（ユーザーエレメント）を持ちますが、これらのリレーションはカスタムのユーザーフィールドではなく、`entries` テーブルの `authorId` カラムに定義されています。

エレメントに他のエレメントとハードコーディングされたリレーションがあり、それらのエレメントを eager-loadable にしたい場合、エレメントクラスに `eagerLoadingMap()` メソッドを追加してください。

```php
use craft\db\Query;
use craft\elements\User;
use craft\helpers\ArrayHelper;

// ...

public static function eagerLoadingMap(array $sourceElements, string $handle)
{
    if ($handle === 'author') {
        // get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select(['id as source', 'authorId as target'])
            ->from(['{{%entries}}'])
            ->where(['and', ['id' => $sourceElementIds], ['not', ['authorId' => null]]])
            ->all();

        return [
            'elementType' => User::class,
            'map' => $map
        ];
    }

    return parent::eagerLoadingMap($sourceElements, $handle);
}
```

このファンクションは、すでに照会されたエレメント（「ソース」エレメント）と、eager-loading のハンドルを受け入れます。どのソースエレメント ID がどの「ターゲット」エレメント ID を eager-load すべきかのマッピングを返すサポートをします。

eager-loaded エレメントが保存されている場所を上書きする必要がある場合、エレメントクラスに `setEagerLoadedElements()` メソッドを追加してください。

```php
public function setEagerLoadedElements(string $handle, array $elements)
{
    if ($handle === 'author') {
        $author = $elements[0] ?? null;
        $this->setAuthor($author);
    } else {
        parent::setEagerLoadedElements($handle, $elements);
    }
}
```

