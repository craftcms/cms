# `craft.fields`

You can access your site’s fields with `craft.fields`.

## Methods

The following methods are available:

### `getFieldByHandle( handle )`

Returns a [FieldModel](https://docs.craftcms.com/api/v2/models/FieldModel.html) object representing a field by its handle.

```twig
{% set body = craft.fields.getFieldByHandle('body') %}
{{ body.instructions }}
```
