# Control Panel Templates

The Control Panel is built using Twig templates, so extending it with new pages should feel familiar if you’ve worked with Twig on the front-end.

Plugins can define templates within the `templates/` folder within their base source folder. Templates within there can be referenced using the plugin’s handle as the template path prefix.

For example if a plugin’s handle is `foo` and it has a `templates/bar.twig` template, that template could be accessed by going to `/admin/foo/bar`, or from Twig by including/extending `foo/bar` (or `foo/bar.twig`).

Modules can have templates too, but they will need to manually define a [template root](template-roots.md) before they are accessible.

## Page Templates

At a minimum, page templates should extend Craft’s [_layouts/cp](https://github.com/craftcms/cms/blob/develop/src/templates/_layouts/cp.html) layout template, set a `title` variable, and define a `content` block.

```twig
{% extends "_layouts/cp" %}
{% set title = "Page Title"|t('plugin-handle') %}

{% block content %}
    <p>Page content goes here</p>
{% endblock %}
```

The following blocks can also be defined, to customize other aspects of the page:

- `header` – Used to output the page header, including the page title and other header elements.
- `pageTitle` – Used to output the page title.
- `contextMenu` – Used to output context menus beside the page title. (For example, the entry revision menu on Edit Entry pages.)
- `actionButton` – Used to output the primary page action button. (For example, the Save button on Edit Entry pages.)
- `sidebar` – Used to output the page sidebar contents.
- `details` – Used to output the detail pane contents.
