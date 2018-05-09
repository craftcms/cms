# EntryTypeModel

Whenever you’re dealing with an entry type in your template, you’re actually working with an EntryTypeModel object.

## Simple Output

Outputting an EntryTypeModel object without attaching a property or method will return the entry type’s handle:

```twig
{{ entry.getType() }}
```

## Properties

EntryTypeModel objects have the following properties:

### `handle`

The entry type’s handle.

### `hasTitleField`

Whether entries using this entry type have a Title field.

### `id`

The entry type’s ID.

### `name`

The entry type’s name.

### `sectionId`

The entry type’s section’s ID.

### `titleLabel`

The label that the Title fields should use, if there is one.

### `titleFormat`

The template that defines what entries’ titles should be set to, if the entry type is set to not show Title fields.
