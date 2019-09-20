# `{% exit %}` Tags

This tag will prevent the rest of the template from executing, and end the request.

```twig
{% set entry = craft.entries.id(entryId).one() %}

{% if not entry %}
    {% exit 404 %}
{% endif %}
```

## Parameters

The `{% exit %}` tag supports the following parameter:

### Status

You can optionally set the HTTP status code that should be included with the response. If you do, Craft will look for the appropriate error template to render. For example, `{% exit 404 %}` will get Craft to return the `404.twig` template. If the template doesn’t exist. Craft will fallback on its own template corresponding to the status code.
