# パスワードの設定フォーム

ユーザーがパスワードを忘れたとき、Craft は新しいパスワードを設定するための URL をメールで送信します。その URL は、コンフィグ設定の <config:setPasswordPath> で定義され、デフォルトでは「setpassword」となります。

サイトのフロントエンドでパスワードのリセットをサポートしたい場合、コンフィグ設定の「setPasswordPath」で指定されたパスにテンプレートを作成する必要があります。

そのテンプレートに、次のコードを記述します。

```twig
<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    {{ actionInput('users/set-password') }}
    {{ hiddenInput('code', code) }}
    {{ hiddenInput('id', id) }}

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

ユーザーがログインに成功すると、コンフィグ設定 <config:setPasswordSuccessPath> で定義されたパスにリダイレクトされます。

