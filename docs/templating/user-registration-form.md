# User Registration Form

If you have Craft Pro and want people to be able to register user accounts on your site, first go to Settings → Users → Settings and make sure that the “Allow Public Registration?” setting is checked.

Then you can create a registration form on the front end, like this:

```twig
<form method="post" accept-charset="UTF-8">
    {{ getCsrfInput() }}
    <input type="hidden" name="action" value="users/saveUser">
    <input type="hidden" name="redirect" value="">

    {% macro errorList(errors) %}
        {% if errors %}
            <ul class="errors">
                {% for error in errors %}
                    <li>{{ error }}</li>
                {% endfor %}
            </ul>
        {% endif %}
    {% endmacro %}

    {% from _self import errorList %}

    <h3><label for="username">Username</label></h3>
    <input id="username" type="text" name="username"
        {%- if account is defined %} value="{{ account.username }}"{% endif -%}>

    {% if account is defined %}
        {{ errorList(account.getErrors('username')) }}
    {% endif %}

    <h3><label for="email">Email</label></h3>
    <input id="email" type="text" name="email"
        {%- if account is defined %} value="{{ account.email }}"{% endif %}>

    {% if account is defined %}
        {{ errorList(account.getErrors('email')) }}
    {% endif %}

    <h3><label for="password">Password</label></h3>
    <input id="password" type="password" name="password">

    {% if account is defined %}
        {{ errorList(account.getErrors('password')) }}
    {% endif %}

    <input type="submit" value="Register">
</form>
```