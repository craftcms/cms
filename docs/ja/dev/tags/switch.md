# `{% switch %}` タグ

「Switch 文」は、いくつかの反復的な `{% if %}` 条件を使う代わりに、複数の可能性がある値に対して変数を比較するクリーンな方法を提供します。

このテンプレートの例では、行列ブロックのタイプによって異なるテンプレートを実行します。

```twig
{% if matrixBlock.type == "text" %}

    {{ matrixBlock.textField|markdown }}

{% elseif matrixBlock.type == "image" %}

    {{ matrixBlock.image[0].getImg() }}

{% else %}

    <p>A font walks into a bar.</p>
    <p>The bartender says, “Hey, we don’t serve your type in here!”</p>

{% endif %}
```

条件文のすべてが同じもの – `matrixBlock.type` – を評価しているため、代わりに `{% switch %}` タグを利用してコードを簡略化できます。

```twig
{% switch matrixBlock.type %}

    {% case "text" %}

        {{ matrixBlock.textField|markdown }}

    {% case "image" %}

        {{ matrixBlock.image[0].getImg() }}

    {% default %}

        <p>A font walks into a bar.</p>
        <p>The bartender says, “Hey, we don’t serve your type in here!”</p>

{% endswitch %}
```

`{% for %}` ループ内で `{% switch %}` タグを使う場合、`{% switch %}` タグの内側で Twig の [ループ変数](https://twig.symfony.com/doc/tags/for.html#the-loop-variable) に直接アクセスすることはできません。代わりに、次のようにアクセスできます。

```twig
{% for matrixBlock in entry.matrixField.all() %}
    {% set loopIndex = loop.index %}

    {% switch matrixBlock.type %}

        {% case "text" %}

            Loop #{{ loopIndex }}

    {% endswitch %}
{% endfor %}
```

ヒント：このタグはあなたが目にしたことがあるかもしれない他の言語の `switch` 実装よりも少し単純です。マッチした `cases` で自動的に終了するため、`break` ステートメントは必要ありません。

