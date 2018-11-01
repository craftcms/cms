# CategoryGroupModel

Whenever you’re dealing with a [category group](../categories.md#category-groups) in your template, you’re actually working with a CategoryGroupModel object.

## Simple Output

Outputting a CategoryGroupModel object without attaching a property or method will return the group’s name:

```twig
<h3>{{ categoryGroup }}</h3>
```


## Properties

CategoryGroupModel objects have the following properties:

### `handle`

The handle of the group.

### `id`

The ID of the group.

### `name`

The name of the group.
