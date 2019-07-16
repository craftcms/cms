# Twig 入門書

これは Craft のテンプレートエンジンである Twig のコアコンセプトの要約です。

これはあくまで入門書であり、 Twig が行うことができるすべての包括的なドキュメントではありません。

より詳しいことは、このページの下段にある「続きを読む」セクションを見るか、[Twig 公式ドキュメント](https://twig.symfony.com/doc/templates.html)を直接参照してください。

## 3種類の Twig タグ

Twig には3種類のタグがあります。

* ロジックタグ
* 出力タグ
* コメントタグ

それぞれについて、詳しく見てみましょう。

### ロジックタグ

ロジックタグは、テンプレート内で起きることをコントロールします。変数を設定したり、条件文をテストしたり、配列をループしたり、他にもいろいろなことができます。

ロジックタグは、それ自身でテンプレートに何も出力しません。

構文は常に `{%` ではじまり `%}` で終わります。その間に記述した内容が、あたなが使うタグになります。

```twig
<p>Is it quitting time?</p>

{% set hour = now|date("G") %}
{% if hour >= 16 and hour < 18 %}
    <p>Yes!</p>
{% else %}
    <p>Nope.</p>
{% endif %}
```

### 出力タグ

出力タグは、レンダリングされた HTML にプリントする責任があります。

構文は常に `{{` ではじまり `}}` で終わります。Twig が文字列として評価できるものであれば、その中にほぼ何でも記述できます。

```twig
<p>The current time is {{ now|date("g:i a") }}.</p>
```

出力タグはテンプレートにアプトプットするためのものなので、 Twig の命令タグ内に記述することは絶対にできません

これらの例は、正しくありません。

```twig
{% set entry = craft.entries.section( {{ sectionId }} ).one() %}
{% set entry = craft.entries.section( {% if filterBySection %} sectionId {% endif %} ) %}
```

こちらは正しいです。

```twig
{% set entry = craft.entries.section( sectionId ).one() %}
{% set entry = craft.entries.section( filterBySection ? sectionId : null ) %}
```

リソース：

* [Twig に付随するタグ](https://twig.symfony.com/doc/tags/index.html)
* [Craft の独自タグ](tags.md)

### コメントタグ

コメントタグを利用して、コード内に将来の自分に向けたコメントを残すことができます。Twig はコメントタグの内容を何も評価しません。単にそれが存在しないものとして振る舞います。

コメント構文は常に `{#` ではじまり `#}` で終わります。

```twig
{# Loop through the recipes #}
```

コメントタグの内側に記述された内容は、HTML コメントとは異なり、最終的なテンプレートにレンダリングされません。

## 変数

Twig の変数は、JavaScript や他のプログラミング言語の変数に似ています。変数には、文字列、配列、ブール値、オブジェクトなど、いろいろな種類があります。それらをファンクションに渡したり、操作したり、出力することができます。

`set` タグを利用して、独自の変数を割り当てることができます。

```twig
{% set style = 'stirred' %}

{{ style }}
```

さらに、すべての Craft テンプレートは、いくつかの[グローバル変数](global-variables.md)があらかじめロードされています。

* 一致する[ルート](../routing.md#dynamic-routes)の結果として読み込まれたテンプレートには、ルートのトークンによって定義された変数があらかじめロードされています。
* 一致する[エントリ](../sections-and-entries.md) URL の結果として読み込まれたテンプレートでは、変数 `entry` を取得できます。（詳細は、[ルーティング](../routing.md)を見てください）

## フィルタ

フィルタで変数を操作できます。構文は、変数名に続けてパイプ（`|`）とフィルタ名となります。

```twig
{{ siteName|upper }}
```

いくつかのフィルタは、パラメータを受け取ります。

```twig
{{ now|date("M d, Y") }}
```

リソース：

* [Twig に付随するフィルタ](https://twig.symfony.com/doc/filters/index.html)
* [Craft の独自フィルタ](filters.md)

## ファンクション

Twig と Craft は、テンプレートタグ内で利用できるいくつかのファンクションを提供します。

```twig
<h3>Watch me count to ten!</h3>
<ul>
    {% for num in range(1, 10) %}
        <li class="{{ cycle(['odd', 'even'], loop.index0) }}">
            {{ num }}
        </li>
    {% endfor %}
</ul>
```

リソース：

* [Twig に付随するファンクション](https://twig.symfony.com/doc/functions/index.html)
* [Craft の独自ファンクション](functions.md)

## 続きを読む

Twig を学ぶためにオンラインで利用できるいくつかの学習リソースがあります。

* [Twig for Template Designers](https://twig.symfony.com/doc/templates.html) は、すべての Twig の機能を詳細なドキュメントです。時として過度に専門的なところもありますが、読んでおくことをお勧めします。
* [Twig Templates in Craft](https://mijingo.com/products/screencasts/twig-templates-in-craft/) は、Craft の Twig を快適に使えるようになることを目的とした、Mijingo によるビデオコースです。
* [Straight up Craft](https://straightupcraft.com/twig-templating) は、Craft での Twig の使い方に関する素晴らしい記事があります。
* [Twig for Designers](https://github.com/brandonkelly/TwigForDesigners) は進行中の eBook で、非開発者が Twig をどのように使えるか説明することを目的としています。

