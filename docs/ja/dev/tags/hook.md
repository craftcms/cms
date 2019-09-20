# `{% hook %}` タグ

このタグは、テンプレート内でプラグインやモジュールに追加の HTML を返すか、利用可能なテンプレート変数を変更する機会を与えます。

```twig
{# Give plugins a chance to make changes here #}
{% hook 'my-custom-hook-name' %}
```

プラグインやモジュールが `{% hook %}` タグで作動できる詳細については、[テンプレートフック](../../extend/template-hooks.md)を参照してください。

