# Tags Fields

Tags fields allow you relate [tags](tags.md) to other elements.

## Settings

Tags fields have the following settings:

- **Source** – Which tag group the field should be able to relate tags from.
- **Selection Label** – The label that should be used on the field’s tag search input.

### Multi-Site Settings

On multi-site installs, the following settings will also be available (under “Advanced”):

- **Relate tags from a specific site?** – Whether to only allow relations to tags from a specific site.

  If enabled, a new setting will appear where you can choose which site.
  
  If disabled, related tags will always be pulled from the current site.

- **Manage relations on a per-site basis** – Whether each site should get its own set of related tags.

## The Field

Tags fields list all of the currently-related tags, with a text input to add new ones.

As you type into the text input, the Tags field will search through the existing tags that belong to the field’s tag group (per its Source setting), and suggest tags in a menu below the text input. If an exact match is not found, the first option in the menu will create a new tag named after the input value.

::: tip
By default you won’t be able to create multiple tags that are too similar in name. You can change that behavior by enabling the <config:allowSimilarTags> config setting.
:::

### Inline Tag Editing

When you double-click on a related tag, a HUD will appear where you can edit the tag’s title and custom fields.

## Templating

If you have an element with an Tags field in your template, you can access its related tags using your Tags field’s handle:

```twig
{% set relatedTags = entry.<FieldHandle> %}
```

That will give you a [tag query](dev/element-queries/tag-queries.md), prepped to output all of the related tags for the given field.

### Examples

To loop through all of the related tags, call [all()](api:craft\db\Query::all()) and then loop over the results:

```twig
{% set relatedTags = entry.<FieldHandle>.all() %}
{% if relatedTags|length %}
    <ul>
        {% for rel in relatedTags %}
            <li><a href="{{ url('tags/'~rel.slug) }}">{{ rel.title }}</a></li>
        {% endfor %}
    </ul>
{% endif %}
```

If you only want the first related tag, call [one()](api:craft\db\Query::one()) instead, and then make sure it returned something:

```twig
{% set rel = entry.<FieldHandle>.one() %}
{% if rel %}
    <p><a href="{{ url('tags/'~rel.slug) }}">{{ rel.title }}</a></p>
{% endif %}
```

If you just need to check if there are any related tags (but don’t need to fetch them), you can call [exists()](api:craft\db\Query::exists()):

```twig
{% if entry.<FieldHandle>.exists() %}
    <p>There are related tags!</p>
{% endif %}
```

You can set [parameters](dev/element-queries/tag-queries.md#parameters) on the tag query as well.

### See Also

* [Tag Queries](dev/element-queries/tag-queries.md)
* <api:craft\elements\Tag>
* [Relations](relations.md)
