# Users Fields

Users fields type allow you relate [users](users.md) to other elements.

## Settings

Users fields have the following settings:

- **Sources** – Which user groups (or other user index sources) the field should be able to relate users from.
- **Limit** – The maximum number of users that can be related with the field at once. (Default is no limit.)
- **Selection Label** – The label that should be used on the field’s selection button.

### Multi-Site Settings

On multi-site installs, the following setting will also be available (under “Advanced”):

- **Manage relations on a per-site basis** – Whether each site should get its own set of related users.

## The Field

Users fields list all of the currently-related users, with a button to select new ones.

Clicking the “Add a user” button will bring up a modal window where you can find and select additional users.

### Inline User Editing

When you double-click on a related user, a HUD will appear where you can edit the user’s custom fields.

## Templating

### Querying Elements with Users Fields

When [querying for elements](dev/element-queries/README.md) that have a Users field, you can filter the results based on the Users field data using a query param named after your field’s handle.

Possible values include:

| Value | Fetches elements…
| - | -
| `':empty:'` | that don’t have any related users.
| `':notempty:'` | that have at least one related user.

```twig
{# Fetch entries with a related user #}
{% set entries = craft.entries()
    .<FieldHandle>(':notempty:')
    .all() %}
```

### Working with Users Field Data

If you have an element with a Users field in your template, you can access its related users using your Users field’s handle:

```twig
{% set relatedUsers = entry.<FieldHandle> %}
```

That will give you a [user query](dev/element-queries/user-queries.md), prepped to output all of the related users for the given field.

To loop through all of the related users, call [all()](api:craft\db\Query::all()) and then loop over the results:

```twig
{% set relatedUsers = entry.<FieldHandle>.all() %}
{% if relatedUsers|length %}
    <ul>
        {% for rel in relatedUsers %}
            <li><a href="{{ url('profiles/'~rel.username) }}">{{ rel.name }}</a></li>
        {% endfor %}
    </ul>
{% endif %}
```

If you only want the first related user, call [one()](api:craft\db\Query::one()) instead, and then make sure it returned something:

```twig
{% set rel = entry.<FieldHandle>.one() %}
{% if rel %}
    <p><a href="{{ url('profiles/'~rel.username) }}">{{ rel.name }}</a></p>
{% endif %}
```

If you just need to check if there are any related users (but don’t need to fetch them), you can call [exists()](api:craft\db\Query::exists()):

```twig
{% if entry.<FieldHandle>.exists() %}
    <p>There are related users!</p>
{% endif %}
```

You can set [parameters](dev/element-queries/user-queries.md#parameters) on the user query as well. For example, to only fetch users in the `authors` group, set the [groupId](dev/element-queries/user-queries.md#groupid) param:

```twig
{% set relatedUsers = entry.<FieldHandle>
    .group('authors')
    .all() %}
```

## See Also

* [User Queries](dev/element-queries/user-queries.md)
* <api:craft\elements\User>
* [Relations](relations.md)
