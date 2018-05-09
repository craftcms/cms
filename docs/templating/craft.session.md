# `craft.session`

You can get info about the active HTTP session with `craft.session`.

## Properties

The following properties are available:

### `isLoggedIn`

Returns whether a user is currently logged in.

```twig
{% if craft.session.isLoggedIn %}
    <a href="{{ logoutUrl }}">Logout</a>
{% endif %}
```

(Note that checking for [`currentUser`](global-variables.md#currentUser) works just as well for this.)

### `rememberedUsername`

Alias of [`getRememberedUsername()`](#getRememberedUsername)

### `returnUrl`

Alias of [`getReturnUrl()`](#returnUrl)

## Methods

The following methods are available:

### `getFlash( key, defaultValue, delete )`

Returns a flash message by its key, or the default value if that flash message doesn’t exist. Pass `false` as the third parameter if you don’t want it deleted right away.

### `getFlashes( delete )`

Returns any flash messages that have been queued up for the user. Pass `false` if you don’t want them to be deleted right away.

### `getRememberedUsername()`

Returns the user’s username, even if they are not logged in, if they have logged in [recently enough](../config-settings.md#rememberUsernameDuration) for Craft to still remember it.

```twig
<input type="text" name="loginName" value="{{ craft.session.getRememberedUsername() }}">
```

### `getReturnUrl()`

Returns the URL the user was trying to access before getting sent to the login page, because they hit a [`{% requireLogin %}`](tags.md#requireLogin) tag.

### `hasFlash()`

Returns whether a flash message exists, by a given key.
