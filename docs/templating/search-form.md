# Search Form

To create a search form, first create a normal HTML form with a ‘search’ input:

```twig
<form action="{{ url('search/results') }}">
    <input type="search" name="q" placeholder="Search">
    <input type="submit" value="Go">
</form>
```

Then, on whatever template your form submits to (e.g. search/results.html), just pull the search query from the GET/POST data, and pass it to the “search” param on [craft.entries](craft.entries.md):

```twig
<h1>Search Results</h1>

{% set query = craft.request.getParam('q') %}
{% set entries = craft.entries.search(query).order('score') %}

{% if entries|length %}
    <p>{{ entries|length }} results:</p>

    <ul>
        {% for entry in entries %}
            <li><a href="{{ entry.url }}">{{ entry.title }}</a></li>
        {% endfor %}
    </ul>
{% else %}
    <p>Your search for “{{ query }}” didn’t return any results.</p>
{% endif %}
```