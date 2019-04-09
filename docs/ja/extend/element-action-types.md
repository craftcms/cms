# エレメントアクションタイプ

プラグインは、<api:craft\base\ElementActionInterface> を実装するクラスを作成することによって、エレメントインデックスページ向けのカスタムアクションタイプを提供できます。そのクラスは（静的メソッドで）アクションタイプについて様々なことを伝える手段として、さらに、そのタイプのアクションが一緒にインスタンス化されるであろうモデルとしての両方の役割を果たします。

便利なものとして、基本アクションタイプの実装を提供する <api:craft\base\ElementAction> を拡張できます。

例えば、Craft 自身のエレメントアクションクラスを参照することもできます。それらは `vendor/craftcms/cms/src/elements/actions/` にあります。

## カスタムエレメントアクションタイプの登録

エレメントインデックスページで表示するためのエレメントアクションを取得するには、エレメントタイプで登録されていなければなりません。

同じプラグインで定義されるカスタムエレメントタイプの場合、エレメントクラスの [defineActions()](element-types.md#index-page-actions) メソッド内にエレメントアクションを含めるだけです。

プラグインのコントロール外にあるエレメントタイプの場合、`registerActions` イベントを使用して登録することもできます。

```php
<?php
namespace ns\prefix;

use craft\base\Element;
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
            $event->actions[] = MyAction::class;
        });

        // ...
    }

    // ...
}
```

