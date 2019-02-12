# Entries Fields

Entries fields allow you to relate [entries](sections-and-entries.md) to other elements.

## Settings

Entries fields have the following settings:

- **Sources** – Which sections (or other entry index sources) the field should be able to relate entries from.
- **Limit** – The maximum number of entries that can be related with the field at once. (Default is no limit.)
- **Selection Label** – The label that should be used on the field’s selection button.

### Multi-Site Settings

On multi-site installs, the following settings will also be available (under “Advanced”):

- **Relate entries from a specific site?** – Whether to only allow relations to entries from a specific site.

  If enabled, a new setting will appear where you can choose which site.

  If disabled, related entries will always be pulled from the current site.

- **Manage relations on a per-site basis** – Whether each site should get its own set of related entries.

## The Field

Entries fields list all of the currently-related entries, with a button to select new ones.

Clicking the “Add an entry” button will bring up a modal window where you can find and select additional entries. You can create new entries from this modal as well, by clicking the “New entry” button.

### Inline Entry Editing

When you double-click on a related entry, a HUD will appear where you can edit the entry’s title and custom fields.

## Templating

### Querying Elements with Entries Fields

When [querying for elements](dev/element-queries/README.md) that have an Entries field, you can filter the results based on the Entries field data using a query param named after your field’s handle.

Possible values include:

| Value | Fetches elements…
| - | -
| `':empty:'` | that don’t have any related entries.
| `':notempty:'` | that have at least one related entry.

```twig
{# Fetch entries with a related entry #}
{% set entries = craft.entries()
    .<FieldHandle>(':notempty:')
    .all() %}
```

### Working with Entries Field Data

If you have an element with an Entries field in your template, you can access its related entries using your Entries field’s handle:

```twig
{% set relatedEntries = entry.<FieldHandle> %}
```

That will give you an [entry query](dev/element-queries/entry-queries.md), prepped to output all of the related entries for the given field.

To loop through all of the related entries, call [all()](api:craft\db\Query::all()) and then loop over the results:

```twig
{% set relatedEntries = entry.<FieldHandle>.all() %}
{% if relatedEntries|length %}
    <ul>
        {% for rel in relatedEntries %}
            <li><a href="{{ rel.url }}">{{ rel.title }}</a></li>
        {% endfor %}
    </ul>
{% endif %}
```

If you only want the first related entry, call [one()](api:craft\db\Query::one()) instead, and then make sure it returned something:

```twig
{% set rel = entry.<FieldHandle>.one() %}
{% if rel %}
    <p><a href="{{ rel.url }}">{{ rel.title }}</a></p>
{% endif %}
```

If you just need to check if there are any related entries (but don’t need to fetch them), you can call [exists()](api:craft\db\Query::exists()):

```twig
{% if entry.<FieldHandle>.exists() %}
    <p>There are related entries!</p>
{% endif %}
```

You can set [parameters](dev/element-queries/entry-queries.md#parameters) on the entry query as well. For example, to only fetch entries in the `news` section, set the [section](dev/element-queries/entry-queries.md#section) param:

```twig
{% set relatedEntries = entry.<FieldHandle>
    .section('news')
    .all() %}
```

## See Also

* [Entry Queries](dev/element-queries/entry-queries.md)
* <api:craft\elements\Entry>
* [Relations](relations.md)
