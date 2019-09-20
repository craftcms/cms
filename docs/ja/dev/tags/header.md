# `{% header %}` タグ

このタグは、レスポンス上に新しい HTTP ヘッダーをセットします。

```twig
{# Tell the browser to cache this page for 30 days #}
{% set expiry = now|date_modify('+30 days') %}

{% header "Cache-Control: max-age=" ~ (expiry.timestamp - now.timestamp) %}
{% header "Pragma: cache" %}
{% header "Expires: " ~ expiry|date('D, d M Y H:i:s', 'GMT') ~ " GMT" %}
```

## パラメータ

`{% header %}` タグは、次のパラメータをサポートしています。

### ヘッダー

`header` の後に文字列として記述することによって、実際のヘッダーを明示します。このパラメータは必須です。

