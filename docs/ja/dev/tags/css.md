# `{% css %}` タグ

`{% css %}` タグは、ページの `<head>` に `<style>` タグを登録するために使用できます。

```css
{% css %}
    .content {
        color: {{ entry.textColor }};
    }
{% endcss %}
```

::: tip
タグを <api:yii\web\View::registerCss()> の中で呼び出し、グローバルな `view` 変数経由でアクセスすることもできます。

```twig
{% set styles = ".content { color: #{entry.textColor}; }" %}
{% do view.registerCss(styles) %}
```

:::

## パラメータ

`{% css %}` タグは、次のパラメータをサポートしています。

### `with`

`<style>` タグに含めるべき、HTML 属性。

```twig
{% css with {type: 'text/css'} %}
```

属性は <api:yii\helpers\BaseHtml::renderTagAttributes()> によってレンダリングされます。

