# Forgot Password Form

You can create a Forgot Password form using the following code:

```twig
<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="users/send-password-reset-email">
    {{ redirectInput('') }}

    <h3><label for="loginName">Username or email</label></h3>
    <input id="loginName" type="text" name="loginName"
        value="{% if loginName is defined %}{{ loginName }}{% else %}{{ craft.app.user.rememberedUsername }}{% endif %}">

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

::: tip
Craft doesn’t ever automatically create links to your Forgot Password page – only your own templates will link to it – so you don’t need to set any config settings with the path to this page, unlike other pages in the password-reset flow (e.g. <config:setPasswordPath> and <config:setPasswordSuccessPath>).
:::
