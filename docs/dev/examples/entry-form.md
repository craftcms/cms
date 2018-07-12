# Entry Form

You can create a new entry form for the front-end of your site using the following code:

```twig
{% macro errorList(errors) %}
    {% if errors %}
        <ul class="errors">
            {% for error in errors %}
                <li>{{ error }}</li>
            {% endfor %}
        </ul>
    {% endif %}
{% endmacro %}

{% from _self import errorList %}

<form method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="entries/save-entry">
    {{ redirectInput('viewentry/{slug}') }}
    <input type="hidden" name="sectionId" value="2">
    <input type="hidden" name="enabled" value="1">

    <label for="title">Title</label>
    <input id="title" type="text" name="title"
        {%- if entry is defined %} value="{{ entry.title }}"{% endif -%}>

    {% if entry is defined %}
        {{ errorList(entry.getErrors('title')) }}
    {% endif %}

    <label for="body">Body</label>
    <textarea id="body" name="fields[body]">
        {%- if entry is defined %}{{ entry.body }}{% endif -%}
    </textarea>

    {% if entry is defined %}
        {{ errorList(entry.getErrors('body')) }}
    {% endif %}

    <input type="submit" value="Publish">
</form>

```

Be sure and adjust the “sectionId” to the actual ID of the section want to save the entry to.

The user submitting the entry will also need to have the permission necessary to create entries for the section they are posting to.

### Editing Entry Form

You can modify the form to save existing entries by adding an “entryId” hidden input to the form:

```twig
<input type="hidden" name="entryId" value="{{ entry.id }}">
```
