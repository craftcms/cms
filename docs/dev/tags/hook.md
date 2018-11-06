# `{% hook %}` Tags

This tag gives plugins and modules an opportunity to hook into the template, to either return additional HTML or make changes to the available template variables.

```twig
{# Give plugins a chance to make changes here #}
{% hook 'my-custom-hook-name' %}
```

See [Template Hooks](../../extend/template-hooks.md) for details on plugins and modules can work with `{% hook %}` tags.
