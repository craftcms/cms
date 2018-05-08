# Tags Fields

Tags fields allow you create [tags](tags.md) and relate them to the parent element.

## Settings

Tags fields have the following settings:

* **Source** – The tag group you want to relate tags from.
* **Target Locale** – Which locale tags should be created/related with (this setting only appears if you’re running Craft Pro with more than one site locale)
* **Selection Label** – The label that should be used on the field’s tag search input.


## The Field

Tags fields list all of the currently selected tags, with a text input to add new ones.

As you type into the text input, the Tags field will search through the existing tags that belong to the field’s tag group, and suggest tags in a menu below the text input. If an exact match is not found, the first option in the menu will actually create a new tag with the title that you typed in.

### Editing Tag Content

Double-clicking on a selected tag will open a modal where you can edit the tag’s title, and any fields you have associated with your tags from Settings → Tags → Fields.

## Templating

If you have an element with a Tags field in your template, you can access its selected tags using your Tags field’s handle:

```twig
{% set tags = entry.tagsFieldHandle %}
```

That will give you an [Element Query](element-queries.md), prepped to output all of the selected tags for the given field. In other words, the line above is really just a shortcut for this:

```twig
{% set tags = craft.tags({
    relatedTo: { sourceElement: entry, field: "tagsFieldHandle" },
    order:     "sortOrder",
    limit:     null
}) %}
```

(See [Relations](relations.md) for more info on the `relatedTo` param.)

### Examples

To check if your Tags field has any selected tags, you can use the `length` filter:

```twig
{% if entry.tagsFieldHandle|length %}
    ...
{% endif %}
```

To loop through the selected tags, you can treat the field like an array:

```twig
{% for tag in entry.tagsFieldHandle %}
    ...
{% endfor %}
```

Rather than typing “`entry.tagsFieldHandle`” every time, you can call it once and set it to another variable:

```twig
{% set tags = entry.tagsFieldHandle %}

{% if tags|length %}

    <h3>Some great tags</h3>
    {% for tag in tags %}
        ...
    {% endfor %}

{% endif %}
```

You can add parameters to the ElementCriteriaModel object as well:

```twig
{% set tags = entry.tagsFieldHandle.order('title') %}
```

If your Tags field is only meant to have a single tag selected, remember that calling your Tags field will still give you the same ElementCriteriaModel, not the selected tag. To get the first (and only) tag selected, use `first()`:

```twig
{% set tag = entry.myTagsField.first() %}

{% if tag %}
    ...
{% endif %}
```


### See Also

* [Element Queries](element-queries.md)
* [Tag Query Params](element-query-params/tag-query-params.md)
* [craft\elements\Tag](https://docs.craftcms.com/api/v3/craft-elements-tag.html)
* [Relations](relations.md)
