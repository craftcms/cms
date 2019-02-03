# Set Password Form

When a user forgets their password, Craft will send them an email with a URL to set a new password. That URL is defined by your <config:setPasswordPath> config setting, which is “setpassword” by default.

If you want the front-end of your site to support password resetting, you need to create a template at the path specified by the ”setPasswordPath” config setting.

Within that template, place the following code:

```twig
<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="users/set-password">
    <input type="hidden" name="code" value="{{ code }}">
    <input type="hidden" name="id" value="{{ id }}">

    <h3><label for="newPassword">New Password</label></h3>
    <input id="newPassword" type="password" name="newPassword">
    {% if errors is defined %}
        <ul class="errors">
            {% for error in errors %}
                <li>{{ error }}</li>
            {% endfor %}
        </ul>
    {% endif %}

    <input type="submit" value="Submit">
</form>
```

After the user successfully logs in, they will be redirected to the path defined by the <config:setPasswordSuccessPath> config setting.
