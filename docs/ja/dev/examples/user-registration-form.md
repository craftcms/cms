# ユーザー登録フォーム

Craft Pro を使用し、サイトで誰でもユーザーアカウントを登録できるようにしたい場合は、はじめに「設定 > ユーザー > 設定」に移動し、「一般登録を許可しますか？」設定にチェックが入っていることを確認します。

そして、次のようなフロントエンドの登録フォームを作成します。

```twig
<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="users/save-user">
    {{ redirectInput('') }}

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
        {%- if user is defined %} value="{{ user.username }}"{% endif -%}>

    {% if user is defined %}
        {{ errorList(user.getErrors('username')) }}
    {% endif %}

    <h3><label for="email">Email</label></h3>
    <input id="email" type="text" name="email"
        {%- if user is defined %} value="{{ user.email }}"{% endif %}>

    {% if user is defined %}
        {{ errorList(user.getErrors('email')) }}
    {% endif %}

    <h3><label for="password">Password</label></h3>
    <input id="password" type="password" name="password">

    {% if user is defined %}
        {{ errorList(user.getErrors('password')) }}
    {% endif %}

    <input type="submit" value="Register">
</form>
```

