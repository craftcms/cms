# フィールドタイプ

プラグインは、<api:craft\base\FieldInterface> および <api:craft\base\FieldTrait> を実装するクラスを作成することによって、カスタムフィールドタイプを提供できます。そのクラスは（静的メソッドで）フィールドタイプについて様々なことを伝える手段として、さらに、そのタイプのフィールドが一緒にインスタンス化されるであろうモデルとしての両方の役割を果たします。

便利なものとして、基本フィールドタイプの実装を提供する <api:craft\base\Field> を拡張できます。

例えば、Craft 自身のフィールドクラスを参照することもできます。それらは `vendor/craftcms/cms/src/fields/` にあります。

## カスタムフィールドタイプの登録

フィールドクラスを作成したら、フィールドサービスに登録する必要があります。それによって、Craft は利用可能なフィールドタイプのリストへ代入する際にそれを知ります。

```php
<?php
namespace ns\prefix;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = MyField::class;
        });

        // ...
    }

    // ...
}
```

