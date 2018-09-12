# Forgot Password Form

You can create a Forgot Password form using the following code:

```twig
<form method="post" accept-charset="UTF-8">
    {{ getCsrfInput() }}
    <input type="hidden" name="action" value="users/sendPasswordResetEmail">
    <input type="hidden" name="redirect" value="">

    <h3><label for="loginName">Username or email</label></h3>
    <input id="loginName" type="text" name="loginName"
        value="{% if loginName is defined %}{{ loginName }}{% else %}{{ craft.session.rememberedUsername }}{% endif %}">

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

While other pages in the forgot password process have Config options to set a custom path ([setPasswordPath](https://docs.craftcms.com/v2/config-settings.html#users), [setPasswordSuccessPath](https://docs.craftcms.com/v2/config-settings.html#users)), this form doesn't need one designated as it's only linked to by you in your Craft project.

Where Craft needs to generate the links for the two examples given above, Craft never takes users to this template.
