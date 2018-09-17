# テンプレートルート

モジュールやプラグインは、コントロールパネル、または、フロントエンドテンプレート向けに、カスタムの「テンプレートルート」を登録できます。

テンプレートルートは、定義済みのテンプレートパス接頭辞から他のテンプレートにアクセスできる、テンプレートを含むディレクトリです。

例えば、`_utils/macros.twig` からアクセスできる共通の Twig ユーティリティマクロを提供するプラグインを作成できます。

そのために、[EVENT_REGISTER_SITE_TEMPLATE_ROOTS](api:craft\web\View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS) イベントを使用します。

```php
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use yii\base\Event;

public function init()
{
    parent::init();
    
    Event::on(
        View::class,
        View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
        function(RegisterTemplateRootsEvent $event) {
            $event->roots['_utils'] = __DIR__ . '/template-utils';
        }
    );
}
```

新しいコントロールパネルのテンプレートルートを登録する場合、代わりに [EVENT_REGISTER_CP_TEMPLATE_ROOTS](api:craft\web\View::EVENT_REGISTER_CP_TEMPLATE_ROOTS) イベントを使用してください。

## プラグインコントロールパネルのテンプレート

プラグインは自動的に追加され、プラグインハンドルにちなんで名付けられた、プラグインのベースソースフォルダ内にある `templates/` フォルダを指す、コントロールパネルのテンプレートルートを取得します。

