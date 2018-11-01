# UserGroupModel

Whenever you’re dealing with a user group in your template, you’re actually working with a UserGroupModel object.

## Simple Output

Outputting a UserGroupModel object without attaching a property or method will return the group’s name:

```twig
<li>{{ group }}</li>
```

## Properties

UserGroupModel objects have the following properties:

### `id`

The ID of the user group.

### `name`

The name of the user group.

### `handle`

The handle of the user group.


## Methods

UserGroupModel objects have the following methods:

### `can( 'permission' )`

Returns whether the user group has a given permission.
