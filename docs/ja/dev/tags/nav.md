# `{% nav %}` タグ

このタグは、[ストラクチャー構造](../../sections-and-entries.md#section-types)や[カテゴリグループ](../../categories.md)のエントリの階層的なナビゲーションメニューを作成するのに役立ちます。

```twig
{% set entries = craft.entries.section('pages').all() %}

<ul id="nav">
    {% nav entry in entries %}
        <li>
            <a href="{{ entry.url }}">{{ entry.title }}</a>
            {% ifchildren %}
                <ul>
                    {% children %}
                </ul>
            {% endifchildren %}
        </li>
    {% endnav %}
</ul>
```

## パラメータ

`{% nav %}` タグは、次のパラメータを持っています。

### アイテム名

「`{% nav`」に続く最初のものは、例えば `item`、`entry`、または `category` のような、ループ内のそれぞれのアイテムを表すために使用する変数名です。この変数名を利用して、ループ内のアイテムを参照します。

### `in`

次に「`in`」という単語の記述が必要で、その後にタグがループ処理するエントリの配列が続きます。これは実際の配列、または [ElementCriteriaModel]() オブジェクトです。

警告：`{% nav %}` タグは特定の（階層的な）順序でエレメントを照会する必要があります。そのため、このタグと関連して `order` 基準パラメータを上書きしないよう確認してください。

## 子エレメントの表示

ループ内の現在のエレメントの子を表示するには、`{% children %}` タグを使用します。Craft がこのタグを取得すると、エレメントの子をループし、`{% nav %}` と `{% endnav %}` タグの間に定義された同じテンプレートをその子に適用します。

エレメントが実際に子を持っているときだけ、子を取り囲む追加 HTML を表示したい場合、`{% children %}` タグを `{% ifchildren %}` と `{% endifchildren %}` タグで囲みます。

ヒント：`{% nav %}` タグは、エレメントを階層的に表示したい、かつ、DOM で階層構造を表現したいとき_だけ_使用するべきです。エレメントを直線的にループしたい場合、代わりに Twig の [for](https://twig.symfony.com/doc/tags/for.html) タグを使ってください。

