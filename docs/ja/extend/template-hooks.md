# テンプレートフック

Craft テンプレートでは、[hook](../dev/tags/hook.md) タグを使用してモジュールやプラグインにフックする機会を与えることができます。

```twig
{# Give plugins a chance to make changes here #}
{% hook 'my-custom-hook-name' %}
```

プラグインやモジュールは <api:craft\web\View::hook()> を使用してテンプレートフックに呼び出されることで、メソッドを登録できます。

```php
Craft::$app->view->hook('my-custom-hook-name', function(array &$context) {
    $context['foo'] = 'bar';
    return '<p>Hey!</p>';
});
```

コールバックメソッドは、現在のテンプレートのコンテキスト（現在定義されているすべてのテンプレート変数）を表す `$context` 引数を渡します。この配列を変更すると、`{% hook %}` タグに続くすべてのタグのテンプレートの変数が変更されます。

このメソッドは、テンプレート内の `{% hook %}` タグがある場所に出力される文字列をオプションで返すことができます。

