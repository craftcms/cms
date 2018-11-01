# `craft.fields`

You can access your site’s fields with `craft.fields`.

## Methods

The following methods are available:

### `getFieldByHandle( handle )`

Returns a <api:Craft\FieldModel> object representing a field by its handle.

```twig
{% set body = craft.fields.getFieldByHandle('body') %}
{{ body.instructions }}
```
