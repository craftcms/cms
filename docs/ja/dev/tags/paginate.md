# `{% paginate %}` タグ

このタグは、複数ページにわたるエレメントのセットを簡単にページ割りできます。

```twig
{% paginate craft.entries.section('blog').limit(10) as pageInfo, pageEntries %}

{% for entry in pageEntries %}
    <article>
        <h1>{{ entry.title }}</h1>
        {{ entry.body }}
    </article>
{% endfor %}

{% if pageInfo.prevUrl %}<a href="{{ pageInfo.prevUrl }}">Previous Page</a>{% endif %}
{% if pageInfo.nextUrl %}<a href="{{ pageInfo.nextUrl }}">Next Page</a>{% endif %}
```

ページ付けされた URL は最初のページ URL と同一になりますが、最後に「/p_X_」（_X_ はページ番号）が追加されます。例：`http://my-project.test/news/p2`。

::: tip
URL の実際のページ番号の前にあるものをカスタマイズするために、コンフィグ設定の <config:pageTrigger> を使用することができます。例えば、`'page/'`  をセットすると、ページ付けされた URL は `http://my-project.test/news/page/2` のようになります。
:::

::: warning
リクエストごとに、1つの `{% paginate %}` タグだけを使用しなければなりません。
:::

## パラメータ

`{% paginate %}` タグは、次のパラメータを持っています。

### エレメントの判定基準

`{% paginate %}` タグに渡す最初のものは、ページ割りしたいすべてのエレメントを定義する [Element Query](../element-queries/README.md) オブジェクトです。`limit` パラメータを使用して、ページごとに表示するエレメント数を定義します。

警告：このパラメータは、実際の ElementCriteriaModel オブジェクトである必要があります。エレメントの配列ではありません。そのため、オブジェクトの `all()` をコールしないでください。

### `as`

次に「`as`」の記述が必要で、その後に1つまたは2つの変数名が続きます。

* `as pageInfo, pageEntries`
* `as pageEntries`

実際の変数名はあなた次第です。ただし、ここに変数名を1つだけ指定した場合、変数 `pageInfo` はデフォルトで「`paginate`」と呼ばれます。

ここで設定されることは、次の通りです。

* `pageInfo` には、現在のページに関する情報や他のページへのリンクを作成するためのいくつかのヘルパーメソッド（[詳細](#the-pageInfo-variable)は以下）を提供する <api:craft\web\twig\variables\Paginate> オブジェクトがセットされます。
* `pageEntries` には、現在のページに属するエレメントの配列がセットされます。

## エレメントの表示

`{% paginate %}` タグは現在のページのエレメントを実際に出力するわけではありません。すなわち、現在のページにあるべき配列を与えるだけです（`as` パラメータで定義された変数によって参照されます）。

`{% paginate %}` タグに続けて、[for](https://twig.symfony.com/doc/tags/for.html) タグを使用することでこのページのエレメントをループする必要があります。

```twig
{% paginate craft.entries.section('blog').limit(10) as pageEntries %}

{% for entry in pageEntries %}
    <article>
        <h1>{{ entry.title }}</h1>
        {{ entry.body }}
    </article>
{% endfor %}
```

## `pageInfo` 変数

`pageInfo` 変数（または、あなたが命名した変数、もしくは、デフォルトの `paginate`）は次のプロパティやメソッドを提供します。

* **`pageInfo.first`** – 現在のページの最初のエレメントのオフセット。
* **`pageInfo.last`** – 現在のページの最後のエレメントのオフセット。
* **`pageInfo.total`** – すべてのページのエレメントの合計数。
* **`pageInfo.currentPage`** – 現在のページ番号。
* **`pageInfo.totalPages`** – すべてのページ数。
* **`pageInfo.prevUrl`** – 前のページの URL、または、最初のページにいる場合は `null`。
* **`pageInfo.nextUrl`** – 次のページの URL、または、最後のページにいる場合は `null`。
* **`pageInfo.firstUrl`** – 最初のページの URL。
* **`pageInfo.lastUrl`** – 最後のページの URL。
* **`pageInfo.getPageUrl( page )`** – 指定されたページ番号の URL、または、ページが存在しない場合は `null` を返します。
* **`pageInfo.getPrevUrls( [dist] )`** – キーにページ番号がセットされた、前のページの URL の配列を返します。URL は昇順で返されます。現在のページから到達可能な最大距離をオプションとして渡すことができます。
* **`pageInfo.getNextUrls( [dist] )`** – キーにページ番号がセットされた、次のページの URL の配列を返します。URL は昇順で返されます。現在のページから到達可能な最大距離をオプションとして渡すことができます。
* **`pageInfo.getRangeUrls( start, end )`** – キーにページ番号がセットされた、指定したページ番号の範囲のページ URL の配列を返します。

## ナビゲーションの実例

[pageInfo](#the-pageInfo-variable) 変数は、あなたに合ったページナビゲーションを作るための沢山のオプションを提供します。ここにいつくかの一般的な例があります。

### 前 / 次のページリンク

単純に前のページと次のページのリンクを表示させたいなら、次のようにできます。

```twig
{% paginate craft.entries.section('blog').limit(10) as pageInfo, pageEntries %}

{% if pageInfo.prevUrl %}<a href="{{ pageInfo.prevUrl }}">Previous Page</a>{% endif %}
{% if pageInfo.nextUrl %}<a href="{{ pageInfo.nextUrl }}">Next Page</a>{% endif %}
```

前、または、次のページが常に存在するとは限らないため、これらのリンクを条件文でラップしていることに注意してください。

### 最初 / 最後のページリンク

最初のページと最後のページのリンクをミックスすることもできます。

```twig
{% paginate craft.entries.section('blog').limit(10) as pageInfo, pageEntries %}

<a href="{{ pageInfo.firstUrl }}">First Page</a>
{% if pageInfo.prevUrl %}<a href="{{ pageInfo.prevUrl }}">Previous Page</a>{% endif %}
{% if pageInfo.nextUrl %}<a href="{{ pageInfo.nextUrl }}">Next Page</a>{% endif %}
<a href="{{ pageInfo.lastUrl }}">Last Page</a>
```

最初と最後のページは常に存在するため、条件文でこれらをラップする理由はありません。

### 近くのページリンク

おそらく現在のページ番号周辺の、近くのページのリストを作りたい場合、同様にできます。

```twig
{% paginate craft.entries.section('blog').limit(10) as pageInfo, pageEntries %}

<a href="{{ pageInfo.firstUrl }}">First Page</a>
{% if pageInfo.prevUrl %}<a href="{{ pageInfo.prevUrl }}">Previous Page</a>{% endif %}

{% for page, url in pageInfo.getPrevUrls(5) %}
    <a href="{{ url }}">{{ page }}</a>
{% endfor %}

<span class="current">{{ pageInfo.currentPage }}</span>

{% for page, url in pageInfo.getNextUrls(5) %}
    <a href="{{ url }}">{{ page }}</a>
{% endfor %}

{% if pageInfo.nextUrl %}<a href="{{ pageInfo.nextUrl }}">Next Page</a>{% endif %}
<a href="{{ pageInfo.lastUrl }}">Last Page</a>
```

この例では、現在のページからいずれかの方向に5ページのリンクを表示しているだけです。多かれ少なかれ表示することを望むなら、`getPrevUrls()` と `getNextUrls()` に渡す数値を変更してください。いずれの数値も渡さないよう選択することもできます。その場合、*すべての* 前 / 次のページ URL が返されます。

