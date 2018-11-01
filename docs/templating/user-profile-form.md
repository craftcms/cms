# User Profile Form

If you want to create a form that is able to edit a user’s own profile, you can do so with the following code:

```twig
<form method="post" accept-charset="UTF-8">
    {{ getCsrfInput() }}
    <input type="hidden" name="action" value="users/saveUser">
    <input type="hidden" name="redirect" value="users/{{ currentUser.username }}">
    <input type="hidden" name="userId" value="{{ currentUser.id }}">

    <label for="location">Location</label>
    <input type="text" id="location" name="fields[location]" value="{{ currentUser.location }}">

    <label for="bio">Bio</label>
    <textarea id="bio" name="fields[bio]">{{ currentUser.bio }}</textarea>

    <input type="submit" value="Save Profile">
</form>
```