# `craft.users`

{intro} If you have Craft Pro, you can access your site’s [users](../users.md) from your templates via `craft.users`. It returns an [ElementCriteriaModel](elementcriteriamodel.md) object.

```twig
{% for author in craft.users.group('authors') %}
    <li><a href="{{ url('authors/'~author.id) }}">{{ author.name }}</a></li>
{% endfor %}
```

## Parameters

`craft.users` supports the following parameters:

### `admin`

Only fetch admin users. Possible values include `'1'` and `'not 1'`.

```twig
{# Fetch all the admins #}
{% set admins = craft.users.admin('1') %}

{# Fetch all the non-admins #}
{% set nonAdmins = craft.users.admin('not 1') %}
```

### `can`

Only fetch users that have a given permission.

```twig
{% set authors = craft.users.can('createEntries:5') %}
```

You can see a list of the permissions Craft comes with [here](../users.md#permissions).

### `email`

Only fetch users with the given email.

### `firstName`

Only fetch users with the given first name.

### `fixedOrder`

If set to `true`, users will be returned in the same order as the IDs entered in the [`id`](#id) param.

### `group`

Only fetch users that belong to a given user group(s), referenced by its handle.

### `groupId`

Only fetch users that belong to a given user group(s), referenced by its ID.

### `id`

Only fetch the user with the given ID.

### `indexBy`

Indexes the results by a given property. Possible values include `'id'` and `'username'`.

### `lastLoginDate`

Fetch users based on their last login date.

### `lastName`

Only fetch users with the given last name.

### `limit`

Limits the results to *X* users.

### `offset`

Skips the first *X* users. For example, if you set `offset(1)`, the would-be second user returned becomes the first.

### `order`

The order the users should be returned in. Possible values include `'username'`, `'firstName'`, `'lastName'`, `'email'`, `'preferredLocale'`, `'status'`, `'lastLoginDate'`, `'dateCreated'`, and `'dateUpdated'`, as well as any textual custom field handles. If you want the users to be sorted in descending order, add “`desc`” after the property name (ex: `'lastLoginDate desc'`). The default value is `'username'`.

### `relatedTo`

Only fetch users that are related to certain other elements. (See [relations](../relations.md) for the syntax options.)

### `search`

Only fetch users that match a given search query. (See [searching](../searching.md) for the syntax and available search attributes.)

### `status`

Only fetch users with the given status. Possible values are `'active'`, `'locked'`, `'suspended'`, `'pending'`, `archived'`, and `null`. The default value is `'active'`. `null` will return all users regardless of status.

### `username`

Only fetch the user with the given username.