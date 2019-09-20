# `{% redirect %}` タグ

このタグは、ブラウザを別の URL にリダイレクトします。

```twig
{% if not user or not user.isInGroup('members') %}
    {% redirect "pricing" %}
{% endif %}
```

## パラメータ

`{% redirect %}` タグは、次のパラメータを持っています。

### URL

「`{% redirect`」と入力したすぐ後に、ブラウザがリダイレクトする場所をタグに伝える必要があります。完全な URL を与えることも、パスだけ指定することもできます。

### ステータスコード

デフォルトでは、 リダイレクトはステータスコード `302` を持っていて、リクエストされた URL がリダイレクトされた URL に _一時的に_ 移動されたことをブラウザに伝えます。

リダイレクトのレスポンスに伴うステータスコードは、URL の直後に入力することでカスタマイズできます。例えば、次のコードは `301` リダイレクト（永続的）を返します。

```twig
{% redirect "pricing" 301 %}
```

### フラッシュメッセージ

`with notice`、および / または、`with error` パラメータを使用して、次のリクエスト時にユーザーへ表示するフラッシュメッセージをオプションでセットできます。

```twig
{% if not currentUser.isInGroup('members') %}
    {% redirect "pricing" 301 with notice "You have to be a member to access that!" %}
{% endif %}
```

