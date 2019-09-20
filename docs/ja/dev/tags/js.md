# `{% js %}` タグ

`{% js %}` タグは、ページに `<script>` タグを登録するために使用できます。

```javascript
{% js %}
    _gaq.push([
        "_trackEvent",
        "Search",
        "{{ searchTerm|e('js') }}"
    ]);
{% endjs %}
```

::: tip
タグを <api:yii\web\View::registerJs()> の中で呼び出し、グローバルな `view` 変数経由でアクセスすることもできます。

```twig
{% set script = '_gaq.push(["_trackEvent", "Search", "'~searchTerm|e('js')~'"' %}
{% do view.registerJs(script) %}
```

:::

## パラメータ

`{% js %}` タグは、次のパラメータをサポートしています。

### 位置

次の位置キーワードのいずれかを使用して、ページの `<script>` を追加する場所を指定できます。

| キーワード | 説明 |
| ------- | ----------- |
| `at head` | ページの `<head>` 内 |
| `at beginBody` | ページの `<body>` の冒頭 |
| `at endBody` | ページの `<body>` の最後 |
| `on load` | ページの `<body>` の最後、`jQuery(window).load()` の中で |
| `on ready` | ページの `<body>` の最後、 `jQuery(document).ready()` の中で |

```twig
{% js at head %}
```

デフォルトでは、`at endBody` が使用されます。

::: warning
`on load` または `on ready` に位置をセットすると、（テンプレートがすでに独自のコピーを含めている場合でも）Craft はページに jQuery の内部コピーを読み込みます。そのため、フロントエンドのテンプレートで利用するのは避けてください。
:::

