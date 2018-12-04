# Search Form

To create a search form, first create a normal HTML form with a `search` input:

```twig
<form action="{{ url('search/results') }}">
    <input type="search" name="q" placeholder="Search">
    <input type="submit" value="Go">
</form>
```

Then, on whatever template your form submits to (e.g. `search/results.twig`), just pull the search query from the `GET`/`POST` data, and pass it to the `search` [entry query param](../element-queries/entry-queries.md#search):

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
