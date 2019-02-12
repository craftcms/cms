# User Profile Form

If you want to create a form that is able to edit a user’s own profile, you can do so with the following code:

```twig
<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="users/save-user">
    {{ redirectInput('users/'~currentUser.username) }}
    <input type="hidden" name="userId" value="{{ currentUser.id }}">

    <label for="location">Location</label>
    <input type="text" id="location" name="fields[location]" value="{{ currentUser.location }}">

    <label for="first-name">First Name</label>
    <input type="text" id="first-name" name="firstName" value="{{ currentUser.firstName }}">

    <label for="last-name">Last Name</label>
    <input type="text" id="last-name" name="lastName" value="{{ currentUser.lastName }}">

    <label for="bio">Bio</label>
    <textarea id="bio" name="fields[bio]">{{ currentUser.bio }}</textarea>

    <input type="submit" value="Save Profile">
</form>
```

::: tip
Only custom field inputs should use the `fields[<FieldHandle>]` name format, not native user fields.
:::
