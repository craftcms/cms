# TagGroupModel

Whenever you’re dealing with a [tag group](../tags.md#tag-groups) in your template, you’re actually working with a TagGroupModel object.

## Simple Output

Outputting a TagGroupModel object without attaching a property or method will return the group’s name:

```twig
<h3>{{ tag.getGroup() }}</h3>
```

## Properties

TagGroupModel objects have the following properties:

### `handle`

The handle of the group.

### `id`

The ID of the group.

### `name`

The name of the group.
