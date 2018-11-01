# コントロールパネルのセクション

モジュールとプラグインは、[EVENT_REGISTER_CP_NAV_ITEMS](api:craft\web\twig\variables\Cp::EVENT_REGISTER_CP_NAV_ITEMS) イベントを使用して新しいセクションをコントロールパネルに追加できます。

```php
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use yii\base\Event;

public function init()
{
    parent::init();
    
    Event::on(
        Cp::class,
        Cp::EVENT_REGISTER_CP_NAV_ITEMS,
        function(RegisterCpNavItemsEvent $event) {
            $event->navItems[] = [
                'url' => 'section-url',
                'label' => 'Section Label',
                'icon' => '@ns/prefix/path/to/icon.svg',
            ];
        }
    );
    
    // ...
}
```

[navItems](api:craft\events\RegisterCpNavItemsEvent::$navItems) 配列内のそれぞれの項目は、次のキーを持つことができます。

- `url` – ナビゲーション項目がリンクする URL。（<api:craft\helpers\UrlHelper::cpUrl()> を実行します。）
- `label` – ユーザーが目にするナビゲーション項目のラベル。
- `icon` – 使用するアイコン SVG のパス。（エイリアスではじめることができます。）
- `badgeCount` _（オプション）_ – ナビゲーション項目に表示されるバッジの数。
- `subnav` _（オプション）_ – セクションにアクセスしたときに表示される、サブナビゲーション項目の配列。（[サブナビゲーション](#subnavs)を参照してください。）

## サブナビゲーション

セクションがサブナビゲーションを持つ場合、`subnav` 配列内のそれぞれのサブナビゲーション項目は、`url` および `label` キーを持つサブ配列で表される必要があります。

```php
'subnav' => [
    'foo' => ['label' => 'Foo', 'url' => 'section-url/foo'],
    'bar' => ['label' => 'Bar', 'url' => 'section-url/bar'],
    'baz' => ['label' => 'Baz', 'url' => 'section-url/baz'],
],
```

テンプレートでは、`selectedSubnavItem` 変数にナビゲーション項目のキーをセットすることによって、どのサブナビゲーション項目が選択されているかを指定できます。

```twig
{% set selectedSubnavItem = 'bar' %}
```

## プラグインセクション

1つのセクションだけを追加したいプラグインは、[EVENT_REGISTER_CP_NAV_ITEMS](api:craft\web\twig\variables\Cp::EVENT_REGISTER_CP_NAV_ITEMS) イベントを使うのではなく、プライマリプラグインクラスの `$hasCpSection` プロパティで設定できます。

```php
<?php

namespace ns\prefix;

class Plugin extends \craft\base\Plugin
{
    public $hasCpSection = true;

    // ...
}
```

[getCpNavItem()](api:craft\base\PluginInterface::getCpNavItem()) メソッドで上書きすることによって、プラグインのコントロールパネルのナビゲーション項目の外観を変更できます。

```php
public function getCpNavItem()
{
    $item = parent::getCpNavItem();
    $item['badgeCount'] = 5;
    $item['subnav'] = [
        'foo' => ['label' => 'Foo', 'url' => 'plugin-handle/foo'],
        'bar' => ['label' => 'Bar', 'url' => 'plugin-handle/bar'],
        'baz' => ['label' => 'Baz', 'url' => 'plugin-handle/baz'],
    ];
    return $item;
}
```

これをする場合、Craft はプラグインの新しい[ユーザー権限](user-permissions.md)を自動的に追加し、その権限を持つユーザーだけにナビゲーション項目を表示します。

プラグインのセクションをクリックすると、ユーザーは`/admin/plugin-handle` に移動し、プラグインの[テンプレートルート](template-roots.md)（ベースソースフォルダ内の `templates/` フォルダ）内の `index.html` または `index.twig` テンプレートをロードしようと試みます。

::: tip
コントロールパネルのテンプレート開発の詳細については、[コントロールパネルのテンプレート](cp-templates.md)を参照してください。
:::

あるいは、プラグインの `init()` メソッドからコントロールパネルのルートを登録することによって、`/admin/plugin-handle` のリクエストをコントローラーアクション（または、他のテンプレート）にルーティングできます。

```php
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

public function init()
{
    Event::on(
        UrlManager::class,
        UrlManager::EVENT_REGISTER_CP_URL_RULES,
        function(RegisterUrlRulesEvent $event) {
            $event->rules['plugin-handle'] = 'plugin-handle/foo/bar';
        }
    );
}
```

