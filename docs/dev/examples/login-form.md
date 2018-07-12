# Login Form

If you need to login a user from the front-end of your site, you can do so with the following code:

```twig
<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="users/login">

    <h3><label for="loginName">Username or email</label></h3>
    <input id="loginName" type="text" name="loginName"
        value="{{ craft.app.user.rememberedUsername }}">

    <h3><label for="password">Password</label></h3>
    <input id="password" type="password" name="password">

    <label>
        <input type="checkbox" name="rememberMe" value="1">
        Remember me
    </label>

    <input type="submit" value="Login">

    {% if errorMessage is defined %}
        <p>{{ errorMessage }}</p>
    {% endif %}
</form>

<p><a href="{{ url('forgotpassword') }}">Forget your password?</a></p>
```

`craft.session.returnUrl` is set to the original URL that included the `{% requireLogin %}` tag that initiated the redirect to this login form.

By default, users will be redirected based on your `postLoginRedirect` config setting value after logging in. You can override that within your login form using a `redirect` param:

```twig
{{ redirectInput('some/custom/path') }}
```

