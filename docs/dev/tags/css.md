# `{% css %}` Tags

The `{% css %}` tag can be used to register a `<style>` tag in the page’s `<head>`.

```css
{% css %}
    .content {
        color: {{ entry.textColor }};
    }
{% endcss %}
```

::: tip
The tag calls <api:yii\web\View::registerCss()> under the hood, which can also be accessed via the global `view` variable.

```twig
{% set styles = ".content { color: #{entry.textColor}; }" %}
{% do view.registerCss(styles) %}
```
:::

## Parameters

The `{% css %}` tag supports the following parameters:

### `with`

Any HTML attributes that should be included on the `<style>` tag.

```twig
{% css with {type: 'text/css'} %}
```

Attributes will be rendered by <api:yii\helpers\BaseHtml::renderTagAttributes()>.
