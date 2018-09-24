# ユーティリティタイプ

プラグインは、<api:craft\base\UtilityInterface> を実装するクラスを作成することによって、ユーティリティセクション向けのカスタムユーティリティタイプを提供できます。

便利なものとして、基本ユーティリティタイプの実装を提供する <api:craft\base\Utility> を拡張できます。

例えば、Craft 自身のユーティリティクラスを参照することもできます。それらは `vendor/craftcms/cms/src/utilities/` にあります。

## カスタムユーティリティタイプの登録

ユーティリティクラスを作成したら、ユーティリティサービスに登録する必要があります。それによって、Craft は利用可能なユーティリティタイプのリストへ代入する際にそれを知ります。

```php
<?php
namespace ns\prefix;

use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init()
    {
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = MyUtility::class;
        });

        // ...
    }

    // ...
}
```

