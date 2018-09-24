# Twig の拡張

Craft は、プラグイン向けに Twig テンプレート環境を拡張するための2つの方法を提供します。

[[toc]]

## グローバル `craft` 変数の拡張

グローバル `craft` テンプレート変数は、<api:craft\web\twig\variables\CraftVariable> のインスタンスです。例えば、テンプレートが `craft.entries` または `craft.entries()` を参照する際、 その裏で [CraftVariable::entries()](api:craft\web\twig\variables\CraftVariable::entries()) が呼び出されます。

`CraftVariable` インスタンスは、[ビヘイビア](https://www.yiiframework.com/doc/guide/2.0/en/concept-behaviors)、および、[サービス](https://www.yiiframework.com/doc/guide/2.0/en/concept-service-locator)を持つプラグインによって拡張できます。正しいアプローチを選択することは、何を追加しようとしているかに依存します。

- カスタムプロパティ、または、メソッドを直接 `craft` 変数に追加するために、**ビヘイビア**を使用します（例：`craft.foo()`）。
- `craft` 変数に、サービスの「ID」と呼ばれるカスタムプロパティ名でアクセスできるサブオブジェクトを追加するために、**サービス**を使用します（例：`craft.foo.*`）。

プラグインの `init()` メソッドから [EVENT_INIT](api:craft\web\twig\variables\CraftVariable::EVENT_INIT) イベントハンドラを登録することで、`CraftVariable` インスタンスにビヘイビア、または、サービスを付加できます。

```php
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

public function init()
{
    parent::init();
    
    Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
        /** @var CraftVariable $variable */
        $variable = $e->sender;
        
        // Attach a behavior:
        $variable->attachBehaviors([
            MyBehavior::class,
        ]);
        
        // Attach a service:
        $variable->set('serviceId', MyService::class);
    });
}
```

## Twig エクステンションの登録

新しいグローバル変数、ファンクション、フィルター、タグ、演算子、または、テストを Twig に追加したい場合、カスタムの [Twig エクステンション](https://twig.symfony.com/doc/2.x/advanced.html#creating-an-extension) を作成することによって実行できます。

Twig エクステンションは、<api:craft\web\View::registerTwigExtension()> メソッドを呼び出すことで Craft の Twig 環境向けに登録できます。

```php
public function init()
{
    parent::init();
    
    if (Craft::$app->request->getIsSiteRequest()) {
        // Add in our Twig extension
        $extension = new MyTwigExtension();
        Craft::$app->view->registerTwigExtension($extension);
    }
}
```

