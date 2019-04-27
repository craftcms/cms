# 検索フォーム

検索フォームを作成するには、はじめに入力項目 `search` を含む通常の HTML を作成します。

```twig
<form action="{{ url('search/results') }}">
    <input type="search" name="q" placeholder="Search">
    <input type="submit" value="Go">
</form>
```

次に、フォームの送信先にあたるテンプレート（例：`search/results.twig`）で `GET` / `POST` データから検索クエリを取り出し、それを `search` [エントリクエリパラメータ](../element-queries/entry-queries.md#search)に渡します。

```twig
<h1>Search Results</h1>

{% set searchQuery = craft.app.request.getParam('q') %}
{% set entries = craft.entries()
    .search(searchQuery)
    .orderBy('score')
    .all() %}

{% if entries|length %}
    <p>{{ entries|length }} results:</p>

    <ul>
        {% for entry in entries %}
            <li><a href="{{ entry.url }}">{{ entry.title }}</a></li>
        {% endfor %}
    </ul>
{% else %}
    <p>Your search for “{{ searchQuery }}” didn’t return any results.</p>
{% endif %}
```

