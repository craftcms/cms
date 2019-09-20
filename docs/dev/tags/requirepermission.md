# `{% requirePermission %}` Tags

This tag will ensure that the current user is logged in with an account that has a given permission.

```twig
{% requirePermission 'stayUpLate' %}
```

The user can have the permission either directly or through one of their user groups. If they don’t have it, a 403 (Forbidden) error will be served.

See the [Users](../../users.md#permissions) page for a list of available permissions.
