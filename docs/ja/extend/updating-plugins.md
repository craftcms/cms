# Craft 3 向けのプラグインアップデート

Craft 3 は CMS の完全な書き換えで、Yii 2 上で構築されています。Yii 2 の変更のスコープのために、プロセス内のすべてのプラグインを壊すことなく、Craft を移植する実現可能な方法はありませんでした。そのため、私たちはシステムのいくつかの主要なエリアをリファクタリングするための[機会](https://www.urbandictionary.com/define.php?term=double%20transgression%20theory)として捉えました。

リファクタリングの主たるゴールは次の通りでした。

- 新しい[コーディングガイドラインとベストプラクティス](coding-guidelines.md)を確立し、パフォーマンス、明快さ、メンテナンス性を最適化する。
- Craft が不必要に車輪を再発明しているエリアを識別し、それを止める。
- モダンな開発ツールキット（Composer、PostgreSQL など）をサポートする。

最終的な結果はコアの開発とプラグイン開発で同様に、より高速で、よりスリムで、より洗練されたコードベースになりました。あなたにも、楽しんでもらえることを望みます。

::: tip
何かが欠けていると思う場合は、[issue を作成してください](https://github.com/craftcms/docs/issues/new)。
:::

[[toc]]

## ハイレベルなメモ

- Craft は、Yii 2 で構築されました。
- メインのアプリケーションインスタンスは、`craft()` ではなく、`Craft::$app` 経由で利用可能になりました。
- プラグインは、プラグインについていくつかの基本的な情報を定義した `composer.json` ファイルを持たなければならなくなりました。
- プラグインは、Craft や他のプラグインすべてと共有する `Craft\` 名前空間ではなく、独自のルート名前空間を取得し、Craft とプラグインのコードはすべて [PSR-4](https://www.php-fig.org/psr/psr-4/) 仕様に従わなければならなくなりました。
- プラグインは、[Yii モジュール](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules) の拡張になりました。

## 更新履歴

Craft 3 プラグインは、`releases.json` ファイルではなく、`CHANGELOG.md` と名付けられた更新履歴を含める必要があります（[更新履歴とアップデート](changelogs-and-updates.md)を参照してください）。

既存の `releases.json` ファイルがある場合、ターミナル上で次のコマンドを使用することで、更新履歴をそれに素早く変換できます。

```bash
# go to the plugin directory
cd /path/to/my-plugin

# create a CHANGELOG.md from its releases.json
curl https://api.craftcms.com/v1/utils/releases-2-changelog --data-binary @releases.json > CHANGELOG.md
```

## Yii 2

Craft が構築されているフレームワーク の Yii は、2.0 向けに完全に書き直されました。どのように変化したかについて知るには、包括的な[アップグレードガイド](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1)を参照してください。

該当するセクションは、次の通りです。

- [Namespace](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#namespace)
- [Component and Object](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#component-and-object)
- [Object Configuration](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#object-configuration)
- [Events](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#events)
- [Path Aliases](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#path-aliases)
- [Models](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#models)
- [Controllers](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#controllers)
- [Console Applications](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#console-applications)
- [I18N](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#i18n)
- [Assets](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#assets)
- [Helpers](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#helpers)
- [Query Builder](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#query-builder)
- [Active Record](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#active-record)
- [Active Record Behaviors](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#active-record-behaviors)
- [User and IdentityInterface](https://www.yiiframework.com/doc/guide/2.0/en/intro-upgrade-from-v1#user-and-identityinterface)

## サービス名

次のコアサービス名が変更されました。

| 旧 | 新 |
| --------------- | ---------------- |
| `assetSources` | `volumes` |
| `email` | `mailer` |
| `templateCache` | `templateCaches` |
| `templates` | `view` |
| `userSession` | `user` |

## コンポーネント

コンポーネントクラス（エレメントタイプ、フィールドタイプ、ウィジェットタイプなど）は、Craft 3 の新しいデザインパターンに従います。

Craft 2 では、それぞれのコンポーネントはモデル（例：`FieldModel`）とタイプ（例：`PlainTextFieldType`）の2つのクラスによって表されていました。モデルはコンポーネントの主な表現であり、コンポーネントのタイプ（ 例：`id`、`name`、および、`handle`）に関わらず、常にそこにあるであろう共通のプロパティを定義しました。その一方で、タイプは特定のコンポーネントタイプを一意（例：入力の UI）にするものを定義するための責任を負っていました。

Craft 3 では、コンポーネントタイプはモデルにとってあまり重要なクラスではなく、もはや個別の役割を果たしません。

次のように動作します。

- `getInputHtml()` のような必須のコンポーネントメソッドは、インターフェースによって定義されます（例：<api:craft\base\FieldInterface>）。
- `$handle` のような共通プロパティは、trait によって定義されます（例：<api:craft\base\FieldTrait>）。
- コンポーネントタイプのベース実装は、抽象的な基本クラスによって提供されます（例：<api:craft\base\Field>）。
- 基本クラスは、様々なコンポーネントクラスによって拡張されます（例：<api:craft\fields\PlainText>）。

## 翻訳

<api:Craft::t()> は、次の翻訳カテゴリのいずれかがセットされる `$category` 引数を必要とします。

- Yii の翻訳メッセージのための `yii`
- Craft の翻訳メッセージのための `app`
- フロントエンドの翻訳メッセージのための `site`
- プラグイン固有の翻訳メッセージのためのプラグインハンドル

```php
\Craft::t('app', 'Entries')
```

フロントエンドの翻訳メッセージに加えて、`site` カテゴリはコントロールパネルの管理者が定義したラベルのために使用されます。

```php
\Craft::t('app', 'Post a new {section} entry', [
    'section' => \Craft::t('site', $section->name)
])
```

フロントエンドの Twig コードを綺麗に保つために、 `|t` および `|translate` フィルタには特定のカテゴリを必要とせず、デフォルトで `site` になります。そのため、これら2つのタグは同じ出力になります。

```twig
{{ "News"|t }}
{{ "News"|t('site') }}
```

## データベースクエリ

### テーブル名

Craft は、もはやデータベーステーブル接頭辞をテーブル名へ自動的に付加しないため、Yii の `{{%tablename}}` 構文でテーブル名を書く必要があります。

### SELECT クエリ

SELECT クエリは、<api:craft\db\Query> クラスで定義されています。

```php
use craft\db\Query;

$results = (new Query())
    ->select(['column1', 'column2'])
    ->from(['{{%tablename}}'])
    ->where(['foo' => 'bar'])
    ->all();
```

### 操作クエリ

操作クエリは、Craft 2 の [`DbCommand`](https://docs.craftcms.com/api/v2/craft-dbcommand.html) クラスと同様に（`Craft::$app->db->createCommand()` 経由でアクセスされる）<api:craft\db\Command> のヘルパーメソッドから構築できます。

1つの顕著な違いは、ヘルパーメソッドはもはや自動的にクエリを実行しません。そのため、`execute()` の呼び出しを連鎖させる必要があります。

```php
$result = \Craft::$app->db->createCommand()
    ->insert('{{%tablename}}', $rowData)
    ->execute();
```

## エレメントクエリ

`ElementCriteriaModel` は、Craft 3 で[エレメントクエリ](../dev/element-queries/README.md)に置き換えられました。

```php
// Old:
$criteria = craft()->elements->getCriteria(ElementType::Entry);
$criteria->section = 'news';
$entries = $criteria->find();

// New:
use craft\elements\Entry;

$entries = Entry::find()
    ->section('news')
    ->all();
```

## Craft コンフィグ設定

Craft のコンフィグ設定のすべては、`vendor/craftcms/cms/src/config/` にあるいくつかのコンフィグクラスの実際のプロパティに移動されました。新しいコンフィグサービス（<api:craft\services\Config>）は、それらのクラスを返すための Getter メソッド / プロパティを提供します。

```php
// Old:
$devMode = craft()->config->get('devMode');
$tablePrefix = craft()->config->get('tablePrefix', ConfigFile::Db);

// New:
$devMode = Craft::$app->config->general->devMode;
$tablePrefix = Craft::$app->config->db->tablePrefix;
```

## ファイル

- `IOHelper` は、Yii の <api:yii\helpers\BaseFileHelper> を拡張する <api:craft\helpers\FileHelper> で置き換えられました。
- <api:craft\helpers\FileHelper> および <api:craft\services\Path> メソッドから返されるディレクトリパスには、スラッシュが含まれなくなりました。
- Craft のファイルシステムパスは、ハードコードされたスラッシュ（`/`）ではなく、（環境に依存して `/` または `\` のどちらかがセットされる）PHP 定数の `DIRECTORY_SEPARATOR` を使用します。

## イベント

Craft 2 / Yii 1 のイベントハンドルを登録する伝統的な方法は、次の通りです。

```php
$component->onEventName = $callback;
```

これは、コンポーネント上にイベントリスナーを直接登録します。

Craft 3 / Yii 2 では、代わりに <api:yii\base\Component::on()> を使用します。

```php
$component->on('eventName', $callback);
```

Craft 2 は、サービス上にイベントハンドルを登録するために使用できる `craft()->on()` メソッドも提供していました。

```php
craft()->on('elements.beforeSaveElement', $callback);
```

Craft 3 には直接匹敵するものがありません。しかし、一般的に Craft 2 で `craft()->on()` を使用していたイベントハンドラは、Craft 3 で[クラスレベルのイベントハンドラ](https://www.yiiframework.com/doc/guide/2.0/en/concept-events#class-level-event-handlers)を使用する必要があります。

```php
use craft\services\Elements;
use yii\base\Event;

Event::on(Elements::class, Elements::EVENT_BEFORE_SAVE_ELEMENT, $callback);
```

サービスに加えて、まだ初期化されていないコンポーネントやそれらへの参照を追跡することが簡単ではないクラスレベルのイベントハンドラを使用できます。

例えば、行列フィールドが保存されるたびに通知させたい場合、次のようにします。

```php
use craft\events\ModelEvent;
use craft\fields\Matrix;
use yii\base\Event;

Event::on(Matrix::class, Matrix::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    // ...
});
```

## プラグインフック

「プラグインフック」のコンセプトは Craft 3 で削除されました。ここに以前サポートされていたフックと、Craft 3 で同じことをどのように達成できるかのリストがあります。

### 一般フック

#### `addRichTextLinkOptions`

```php
// Old:
public function addRichTextLinkOptions()
{
    return [
        [
            'optionTitle' => Craft::t('Link to a product'),
            'elementType' => 'Commerce_Product',
        ],
    ];
}

// New:
use craft\events\RegisterRichTextLinkOptionsEvent;
use craft\fields\RichText;
use yii\base\Event;

Event::on(RichText::class, RichText::EVENT_REGISTER_LINK_OPTIONS, function(RegisterRichTextLinkOptionsEvent $event) {
    $event->linkOptions[] = [
        'optionTitle' => \Craft::t('plugin-handle', 'Link to a product'),
        'elementType' => Product::class,
    ];
});
```

#### `addTwigExtension`

```php
// Old:
public function addTwigExtension()
{
    Craft::import('plugins.cocktailrecipes.twigextensions.MyExtension');
    return new MyExtension();
}

// New:
\Craft::$app->view->registerTwigExtension($extension);
```

#### `addUserAdministrationOptions`

```php
// Old:
public function addUserAdministrationOptions(UserModel $user)
{
    if (!$user->isCurrent()) {
        return [
            [
                'label'  => Craft::t('Send Bacon'),
                'action' => 'baconater/sendBacon'
            ],
        ];
    }
}

// New:
use craft\controllers\UsersController;
use craft\events\RegisterUserActionsEvent;
use yii\base\Event;

Event::on(UsersController::class, UsersController::EVENT_REGISTER_USER_ACTIONS, function(RegisterUserActionsEvent $event) {
    if ($event->user->isCurrent) {
        $event->miscActions[] = [
            'label' => \Craft::t('plugin-handle', 'Send Bacon'),
            'action' => 'baconater/send-bacon'
        ];
    }
});
```

#### `getResourcePath`

```php
// Old:
public function getResourcePath($path)
{
    if (strpos($path, 'myplugin/') === 0) {
        return craft()->path->getStoragePath().'myplugin/'.substr($path, 9);
    }
}
```

::: warning NOTE
リソースリクエストのコンセプトが Craft 3 で削除されたため、プラグインにリソースリクエストの処理を許可するこのフックには、直接 Craft 3 で匹敵するものがありません。Craft 3 でプラグインがどのようにリソースを提供できるかを知るには[アセットバンドル](asset-bundles.md)を参照してください。
:::

#### `modifyCpNav`

```php
// Old:
public function modifyCpNav(&$nav)
{
    if (craft()->userSession->isAdmin()) {
        $nav['foo'] = [
            'label' => Craft::t('Foo'),
            'url' => 'foo'
        ];
    }
}

// New:
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use yii\base\Event;

Event::on(Cp::class, Cp::EVENT_REGISTER_CP_NAV_ITEMS, function(RegisterCpNavItemsEvent $event) {
    if (\Craft::$app->user->identity->admin) {
        $event->navItems['foo'] = [
            'label' => \Craft::t('plugin-handle', 'Utils'),
            'url' => 'utils'
        ];
    }
});
```

#### `registerCachePaths`

```php
// Old:
public function registerCachePaths()
{
    return [
        craft()->path->getStoragePath().'drinks/' => Craft::t('Drink images'),
    ];
}

// New:
use craft\events\RegisterCacheOptionsEvent;
use craft\utilities\ClearCaches;
use yii\base\Event;

Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS, function(RegisterCacheOptionsEvent $event) {
    $event->options[] = [
        'key' => 'drink-images',
        'label' => \Craft::t('plugin-handle', 'Drink images'),
        'action' => \Craft::$app->path->getStoragePath().'/drinks'
    ];
});
```

#### `registerEmailMessages`

```php
// Old:
public function registerEmailMessages()
{
    return ['my_message_key'];
}

// New:
use craft\events\RegisterEmailMessagesEvent;
use craft\services\SystemMessages;
use yii\base\Event;

Event::on(SystemMessages::class, SystemMessages::EVENT_REGISTER_MESSAGES, function(RegisterEmailMessagesEvent $event) {
    $event->messages[] = [
        'key' => 'my_message_key',
        'heading' => Craft::t('plugin-handle', 'Email Heading'),
        'subject' => Craft::t('plugin-handle', 'Email Subject'),
        'body' => Craft::t('plugin-handle', 'The plain text email body...'),
    ];
});
```

::: tip
<api:Craft::t()> の呼び出し内で heading / subject / body の右側の完全なメッセージを定義するのではなく、プレースホルダ文字列（例：`'email_heading'`） を渡してプラグインの翻訳ファイルに実際の文字列を定義することもできます。
:::

#### `registerUserPermissions`

```php
// Old:
public function registerUserPermissions()
{
    return [
        'drinkAlcohol' => ['label' => Craft::t('Drink alcohol')],
        'stayUpLate' => ['label' => Craft::t('Stay up late')],
    ];
}

// New:
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use yii\base\Event;

Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
    $event->permissions[\Craft::t('plugin-handle', 'Vices')] = [
        'drinkAlcohol' => ['label' => \Craft::t('plugin-handle', 'Drink alcohol')],
        'stayUpLate' => ['label' => \Craft::t('plugin-handle', 'Stay up late')],
    ];
});
```

#### `getCpAlerts`

```php
// Old:
public function getCpAlerts($path, $fetch)
{
    if (craft()->config->devMode) {
        return [Craft::t('Dev Mode is enabled!')];
    }
}

// New:
use craft\events\RegisterCpAlertsEvent;
use craft\helpers\Cp;
use yii\base\Event;

Event::on(Cp::class, Cp::EVENT_REGISTER_ALERTS, function(RegisterCpAlertsEvent $event) {
    if (\Craft::$app->config->general->devMode) {
        $event->alerts[] = \Craft::t('plugin-handle', 'Dev Mode is enabled!');
    }
});
```

#### `modifyAssetFilename`

```php
// Old:
public function modifyAssetFilename($filename)
{
    return 'KittensRule-'.$filename;
}

// New:
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\helpers\Assets;
use yii\base\Event;

Event::on(Assets::class, Assets::EVENT_SET_FILENAME, function(SetElementTableAttributeHtmlEvent $event) {
    $event->filename = 'KittensRule-'.$event->filename;

    // Prevent other event listeners from getting invoked
    $event->handled = true;
});
```

### ルーティングフック

#### `registerCpRoutes`

```php
// Old:
public function registerCpRoutes()
{
    return [
        'cocktails/new' => 'cocktails/_edit',
        'cocktails/(?P<widgetId>\d+)' => ['action' => 'cocktails/editCocktail'],
    ];
}

// New:
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
    $event->rules['cocktails/new'] = ['template' => 'cocktails/_edit'];
    $event->rules['cocktails/<widgetId:\d+>'] = 'cocktails/edit-cocktail';
});
```

#### `registerSiteRoutes`

```php
// Old:
public function registerSiteRoutes()
{
    return [
        'cocktails/new' => 'cocktails/_edit',
        'cocktails/(?P<widgetId>\d+)' => ['action' => 'cocktails/editCocktail'],
    ];
}

// New:
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
    $event->rules['cocktails/new'] = ['template' => 'cocktails/_edit'];
    $event->rules['cocktails/<widgetId:\d+>'] = 'cocktails/edit-cocktail';
});
```

#### `getElementRoute`

```php
// Old:
public function getElementRoute(BaseElementModel $element)
{
    if (
        $element->getElementType() === ElementType::Entry &&
        $element->getSection()->handle === 'products'
    ) {
        return ['action' => 'products/viewEntry'];
    }
}

// New:
use craft\base\Element;
use craft\elements\Entry;
use craft\events\SetElementRouteEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_SET_ROUTE, function(SetElementRouteEvent $event) {
    /** @var Entry $entry */
    $entry = $event->sender;

    if ($entry->section->handle === 'products') {
        $event->route = 'products/view-entry';

        // Prevent other event listeners from getting invoked
        $event->handled = true;
    }
});
```

### エレメントフック

次のフックのセットは、すべてのエレメントタイプで共有されている単一のイベントに結合されました。

これらのそれぞれのために、（*すべての* エレメントタイプ向けにイベントリスナーを登録している）`yii\base\Event::on()` の第一引数に <api:craft\base\Element::class>、または（1つのエレメントタイプのためだけのイベントリスナーを登録している）特定のエレメントタイプのいずれかを渡すことができます。

#### `addEntryActions`, `addCategoryActions`, `addAssetActions`, & `addUserActions`

```php
// Old:
public function addEntryActions($source)
{
    return [new MyElementAction()];
}

// New:
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
    $event->actions[] = new MyElementAction();
});
```

#### `modifyEntrySortableAttributes`, `modifyCategorySortableAttributes`, `modifyAssetSortableAttributes`, & `modifyUserSortableAttributes`

```php
// Old:
public function modifyEntrySortableAttributes(&$attributes)
{
    $attributes['id'] = Craft::t('ID');
}

// New:
use craft\base\Element;
use craft\elements\Entry;
use craft\events\RegisterElementSortOptionsEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_REGISTER_SORT_OPTIONS, function(RegisterElementSortOptionsEvent $event) {
    $event->sortOptions['id'] = \Craft::t('app', 'ID');
});
```

#### `modifyEntrySources`, `modifyCategorySources`, `modifyAssetSources`, & `modifyUserSources`

```php
// Old:
public function modifyEntrySources(&$sources, $context)
{
    if ($context == 'index') {
        $sources[] = [
            'heading' => Craft::t('Statuses'),
        ];

        $statuses = craft()->elements->getElementType(ElementType::Entry)
            ->getStatuses();
        foreach ($statuses as $status => $label) {
            $sources['status:'.$status] = [
                'label' => $label,
                'criteria' => ['status' => $status]
            ];
        }
    }
}

// New:
use craft\base\Element;
use craft\elements\Entry;
use craft\events\RegisterElementSourcesEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_REGISTER_SOURCES, function(RegisterElementSourcesEvent $event) {
    if ($event->context === 'index') {
        $event->sources[] = [
            'heading' => \Craft::t('plugin-handle', 'Statuses'),
        ];

        foreach (Entry::statuses() as $status => $label) {
            $event->sources[] = [
                'key' => 'status:'.$status,
                'label' => $label,
                'criteria' => ['status' => $status]
            ];
        }
    }
});
```

#### `defineAdditionalEntryTableAttributes`, `defineAdditionalCategoryTableAttributes`, `defineAdditionalAssetTableAttributes`, & `defineAdditionalUserTableAttributes`

```php
// Old:
public function defineAdditionalEntryTableAttributes()
{
    return [
        'foo' => Craft::t('Foo'),
        'bar' => Craft::t('Bar'),
    ];
}

// New:
use craft\elements\Entry;
use craft\events\RegisterElementTableAttributesEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_REGISTER_TABLE_ATTRIBUTES, function(RegisterElementTableAttributesEvent $event) {
    $event->tableAttributes['foo'] = ['label' => \Craft::t('plugin-handle', 'Foo')];
    $event->tableAttributes['bar'] = ['label' => \Craft::t('plugin-handle', 'Bar')];
});
```

#### `getEntryTableAttributeHtml`, `getCategoryTableAttributeHtml`, `getAssetTableAttributeHtml`, & `getUserTableAttributeHtml`

```php
// Old:
public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
{
    if ($attribute === 'price') {
        return '$'.$entry->price;
    }
}

// New:
use craft\base\Element;
use craft\elements\Entry;
use craft\events\SetElementTableAttributeHtmlEvent;
use yii\base\Event;

Event::on(Entry::class, Element::EVENT_SET_TABLE_ATTRIBUTE_HTML, function(SetElementTableAttributeHtmlEvent $event) {
    if ($event->attribute === 'price') {
        /** @var Entry $entry */
        $entry = $event->sender;

        $event->html = '$'.$entry->price;

        // Prevent other event listeners from getting invoked
        $event->handled = true;
    }
});
```

#### `getTableAttributesForSource`

```php
// Old:
public function getTableAttributesForSource($elementType, $sourceKey)
{
    if ($sourceKey == 'foo') {
        return craft()->elementIndexes->getTableAttributes($elementType, 'bar');
    }
}
```

::: warning NOTE
エレメントインデックスがレンダリングされる前に、プラグインがエレメントタイプのテーブル属性を完全に変更することを許可するこのフックには、直接 Craft 3 で匹敵するものがありません。Craft 3 で最も近いのは、管理者がエレメントインデックスのソースをカスタマイズする際に、エレメントタイプの利用可能なテーブル属性を変更するために使用できる <api:craft\base\Element::EVENT_REGISTER_TABLE_ATTRIBUTES> イベントです。
:::

## テンプレート変数

テンプレート変数は、もはや Craft 3 のものではありません。しかしながら、プラグインは `init` イベントをリスニングすることで、グローバルな `craft` 変数にカスタムサービスを登録することができます。

```php
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
    /** @var CraftVariable $variable */
    $variable = $event->sender;
    $variable->set('componentName', YourVariableClass::class);
});
```

（`componentName` をあなたが望む `craft` オブジェクトの変数名に置き換えてください。後方互換性のために、古い `camelCased` プラグインハンドルにすることをお勧めします。）

## テンプレートのレンダリング

テンプレートサービスは View コンポーネントに置き換えられました。

```php
// Old:
craft()->templates->render('pluginHandle/path/to/template', $variables);

// New:
\Craft::$app->view->renderTemplate('plugin-handle/path/to/template', $variables);
```

### コントローラーアクションのテンプレート

コントローラーの `renderTemplate()` メソッドは、あまり変更されていません。唯一の違いは、テンプレートの出力やリクエストの最後に使用されていたのに対して、現在ではコントローラーアクションが返すべきレンダリングされたテンプレートを返します。

```php
// Old:
$this->renderTemplate('pluginHandle/path/to/template', $variables);

// New:
return $this->renderTemplate('plugin-handle/path/to/template', $variables);
```

### フロントエンドリクエストのプラグインテンプレートのレンダリング

フロンドエンドリクエストでプラグインが提供するテンプレートをレンダリングしたい場合、View コンポーネントを CP のテンプレートモードに設定する必要があります。

```php
// Old:
$oldPath = craft()->templates->getTemplatesPath();
$newPath = craft()->path->getPluginsPath().'pluginhandle/templates/';
craft()->templates->setTemplatesPath($newPath);
$html = craft()->templates->render('path/to/template');
craft()->templates->setTemplatesPath($oldPath);

// New:
use craft\web\View;

$oldMode = \Craft::$app->view->getTemplateMode();
\Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
$html = \Craft::$app->view->renderTemplate('plugin-handle/path/to/template');
\Craft::$app->view->setTemplateMode($oldMode);
```

## コントロールパネルのテンプレート

プラグインが Craft の`_layouts/cp.html` コントロールパネルレイアウトテンプレートを拡張するプラグインを持つ場合、アップデートが必要がことがいくつかあります。

### `extraPageHeaderHtml`

`extraPageHeaderHtml` 変数のサポートは削除されました。ページヘッダーのプライマリアクションボタンを作成するには、新しい `actionButton` を使用してください。

```twig
{# Old: #}
{% set extraPageHeaderHtml %}
    <a href="{{ url('recipes/new') }}" class="btn submit">{{ 'New recipe'|t('app') }}</a>
{% endset %}

{# New: #}
{% block actionButton %}
    <a href="{{ url('recipes/new') }}" class="btn submit">{{ 'New recipe'|t('app') }}</a>
{% endblock %}
```

### ページ全体のグリッド

`main` ブロックを上書きし、その中にページ全体のグリッドを定義する場合、グリッドアイテムのコンテンツを新しい `content` と `details` ブロックに分割する必要があります。

さらに、すでに持っていたいくつかの `<div class="pane">` は、 通常 `pane` クラスを失っています。

```twig
{# Old: #}
{% block main %}
    <div class="grid first" data-max-cols="3">
        <div class="item" data-position="left" data-colspan="2">
            <div id="recipe-fields" class="pane">
                <!-- Primary Content -->
            </div>
        </div>
        <div class="item" data-position="right">
            <div class="pane meta">
                <!-- Secondary Content -->
            </div>
        </div>
    </div>
{% endblock %}

{# New: #}
{% block content %}
    <div id="recipe-fields">
        <!-- Primary Content -->
    </div>
{% endblock %}

{% block details %}
    <div class="meta">
        <!-- Secondary Content -->
    </div>
{% endblock %}
```

### コントロールパネルテンプレートフック

The following Control Panel [template hooks](template-hooks.md) have been renamed:

| 旧 | 新 |
| -------------------------------- | ---------------------------- |
| `cp.categories.edit.right-pane` | `cp.categories.edit.details` |
| `cp.entries.edit.right-pane` | `cp.entries.edit.details` |
| `cp.users.edit.right-pane` | `cp.users.edit.details` |

## リソースリクエスト

Craft 3 にはリソースリクエストのコンセプトがありません。フロントエンドリソースの働きについての情報は、[アセットバンドル](asset-bundles.md) を参照してください。

## 任意の HTML の登録

ページのどこかに任意の HTML を含めたい場合、View コンポーネントで `beginBody` または `endBody` イベントを使用してください。

```php
// Old:
craft()->templates->includeFootHtml($html);

// New:
use craft\web\View;
use yii\base\Event;

Event::on(View::class, View::EVENT_END_BODY, function(Event $event) {
    // $html = ...
    echo $html;
});
```

## バックグラウンドタスク

Craft のタスクサービスは、[Yii 2 Queue Extension](https://github.com/yiisoft/yii2-queue) を備えたジョブキューに置き換えられました。

プラグインがカスタムタスクタイプを提供する場合、それらをジョブに変換する必要があります。

```php
// Old:
class MyTask extends BaseTask
{
    public function getDescription()
    {
        return 'Default description';
    }

    public function getTotalSteps()
    {
        return 5;
    }

    public function runStep($step)
    {
        // do something...
        return true;
    }
}

// New:
use craft\queue\BaseJob;

class MyJob extends BaseJob
{
    public function execute($queue)
    {
        $totalSteps = 5;
        for ($step = 0; $step < $steps; $step++)
        {
            $this->setProgress($queue, $step / $totalSteps);
            // do something...
        }
    }

    protected function defaultDescription()
    {
        return 'Default description';
    }
}
```

ジョブをキューに追加する方法も少し異なります。

```php
// Old:
craft()->tasks->createTask('MyTask', 'Custom description', array(
    'mySetting' => 'value',
));

// New:
Craft::$app->queue->push(new MyJob([
    'description' => 'Custom description',
    'mySetting' => 'value',
]));
```

## アップグレードマイグレーションの記述

Craft 2 インストール向けにプラグインにマイグレーションパスを与える必要があるかもしれません。それによって、それらが立ち往生することはなくなります。

Craft がプラグインを**アップデート**なのか、**新規インストール**なのか判断させることを最初に決定する必要があります。プラグインハンドルが（`UpperCamelCase` から `kebab-case` になる他に）変更されない場合、Craft は新しいバージョンの**アップデート**とみなします。しかし、ハンドルがより重要な形で変わっているなら、Craft はそれを認識せず、完全に新しいプラグインとして判断します。

ハンドルが（一般的に）同じ名前で止まる場合、“`craft3_upgrade`” のように名付けられた新しい[マイグレーション](plugin-migrations.md)を作成してください。アップグレードコードは、他のマイグレーション同様に `safeUp()` メソッドに入れます。

ハンドルが変更されている場合、代わりに[インストールマイグレーション](plugin-migrations.md#install-migrations)にアップグレードコードを配置する必要があります。これを出発点として使用してください。

```php
<?php
namespace ns\prefix\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        if ($this->_upgradeFromCraft2()) {
            return;
        }

        // Fresh install code goes here...
    }

    private function _upgradeFromCraft2()
    {
        // Fetch the old plugin row, if it was installed
        $row = (new \craft\db\Query())
            ->select(['id', 'settings'])
            ->from(['{{%plugins}}'])
            ->where(['in', 'handle', ['old-handle', 'oldhandle']])
            ->one();

        if (!$row) {
            return false;
        }

        // Update this one's settings to old values
        $this->update('{{%plugins}}', [
            'settings' => $row['settings']
        ], ['handle' => 'new-handle']);

        // Delete the old row
        $this->delete('{{%plugins}}', ['id' => $row['id']]);

        // Any additional upgrade code goes here...

        return true;
    }

    public function safeDown()
    {
        // ...
    }
}
```

プラグインの以前のハンドル（`kebab-case` と `onewordalllowercase`）を `old-handle` と `oldhandle` に置き換えてください。そして、`_upgradeFromCraft2()` メソッドの最後（`return` 文の前）に、追加のアップグレードコードを配置してください。（プラグインの新規インストール向けの）通常のインストールマイグレーションコードは、`safeUp()` の最後に入れる必要があります。

### コンポーネントクラス名

プラグインがカスタムエレメントタイプ、フォールドタイプ、または、ウィジェットタイプを提供する場合、新しいクラス名とマッチする適切なテーブルの `type` カラムをアップデートする必要があります。

#### エレメント

```php
$this->update('{{%elements}}', [
    'type' => MyElement::class
], ['type' => 'OldPlugin_ElementType']);
```

#### フィールド

```php
$this->update('{{%fields}}', [
    'type' => MyField::class
], ['type' => 'OldPlugin_FieldType']);
```

#### ウィジェット

```php
$this->update('{{%widgets}}', [
    'type' => MyWidget::class
], ['type' => 'OldPlugin_WidgetType']);
```

### ロケールの外部キー

プラグインが Craft 2 の `locales` テーブルにカスタム外部キーを作成していた場合、Craft 3 のアップグレードでは、`locales` テーブルがもはや存在しないため、代わりに`sites` テーブルの外部キーを付けた新しいカラムが自動的に追加されます。

データは問題なく動作するはずですが、古いカラムを削除し、Craft によって新しく作成されたものをリネームすることを望むでしょう。

```php
// Drop the old locale FK column
$this->dropColumn('{{%tablename}}', 'oldName');

// Rename the new siteId FK column
MigrationHelper::renameColumn('{{%tablename}}', 'oldName__siteId', 'newName', $this);
```

