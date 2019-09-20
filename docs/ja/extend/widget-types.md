# ウィジェットタイプ

プラグインは、<api:craft\base\WidgetInterface> および <api:craft\base\WidgetTrait> を実装するクラスを作成することによって、ダッシュボード向けのカスタムウィジェットタイプを提供できます。そのクラスは（静的メソッドで）ウィジェットタイプについて様々なことを伝える手段として、さらに、そのタイプのウィジェットが一緒にインスタンス化されるであろうモデルとしての両方の役割を果たします。

便利なものとして、基本ウィジェットタイプの実装を提供する <api:craft\base\Widget> を拡張できます。

例えば、Craft 自身のウィジェットクラスを参照することもできます。それらは `vendor/craftcms/cms/src/widgets/` にあります。

## カスタムウィジェットタイプの登録

ウィジェットクラスを作成したら、ダッシュボードサービスに登録する必要があります。それによって、Craft は利用可能なウィジェットタイプのリストへ代入する際にそれを知ります。

```php
<?php
namespace ns\prefix;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Dashboard;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = MyWidget::class;
        });

        // ...
    }

    // ...
}
```

