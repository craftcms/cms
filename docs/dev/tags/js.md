# `{% js %}` Tags

The `{% js %}` tag can be used to register a `<script>` tag on the page.

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
The tag calls <api:yii\web\View::registerJs()> under the hood, which can also be accessed via the global `view` variable.

```twig
{% set script = '_gaq.push(["_trackEvent", "Search", "'~searchTerm|e('js')~'"' %}
{% do view.registerJs(script) %}
```
:::

## Parameters

The `{% js %}` tag supports the following parameters:

### Position

You can specify where the `<script>` tag should be added to the page using one of these position keywords:

| Keyword | Description
| ------- | -----------
| `at head` | In the page’s `<head>`
| `at beginBody` | At the beginning of the page’s `<body>`
| `at endBody` | At the end of the page’s `<body>`
| `on load` | At the end of the page’s `<body>`, within `jQuery(window).load()`
| `on ready` | At the end of the page’s `<body>`, within `jQuery(document).ready()`

```twig
{% js at head %}
```

By default, `at endBody` will be used.

::: warning
Setting the position to `on load` or `on ready` will cause Craft to load its internal copy of jQuery onto the page (even if the template is already including its own copy), so you should probably avoid using them in front-end templates.
:::
