# ボリュームタイプ

プラグインは、<api:craft\base\VolumeInterface> および <api:craft\base\VolumeTrait> を実装するクラスを作成することによって、カスタムアセットボリュームタイプを提供できます。そのクラスは（静的メソッドで）ボリュームタイプについて様々なことを伝える手段として、さらに、そのタイプのボリュームが一緒にインスタンス化されるであろうモデルとしての両方の役割を果たします。

便利なものとして、[Flysystem](https://flysystem.thephpleague.com/) アダプタ向けに最適化された基本ボリュームタイプの実装を提供する <api:craft\base\Volume> を拡張することができます。

例えば、Craft 自身のボリュームクラスを参照することもできます。それらは `vendor/craftcms/cms/src/volumes/` にあります。

## カスタムボリュームタイプの登録

ボリュームクラスを作成したら、ボリュームサービスに登録する必要があります。それによって、Craft は利用可能なボリュームタイプのリストへ代入する際にそれを知ります。

```php
<?php
namespace ns\prefix;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Volumes;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Volumes::class, Volumes::EVENT_REGISTER_VOLUME_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = MyVolume::class;
        });

        // ...
    }

    // ...
}
```

