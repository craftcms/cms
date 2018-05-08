# Users Fields

Users fields type allow you relate [users](users.md) to the parent element.

## Settings

Users fields have the following settings:

* **Sources** – The user groups you want to relate users from. (Default is “All”)
* **Limit** – The maximum number of users that can be related with the field at once. (Default is no limit.)
* **Selection Label** – The label that should be used on the field’s selection button.


## The Field

Users fields list all of the currently selected users, with a button to select new ones:

Clicking the “Add a user button will bring up a modal window where you can find and select additional users:

## Templating

If you have an element with a Users field in your template, you can access its selected users using your Users field’s handle:

```twig
{% set users = entry.usersFieldHandle %}
```

That will give you an [element query](element-queries.md), prepped to output all of the selected users for the given field. In other words, the line above is really just a shortcut for this:

```twig
{% craft.users({
    relatedTo: { sourceElement: entry, field: "usersFieldHandle" },
    orderBy:     "sortOrder",
    limit:     null
}) %}
```

(See [Relations](relations.md) for more info on the `relatedTo` param.)

### Examples

To check if your Users field has any selected users, you can use the `length` filter:

```twig
{% if entry.usersFieldHandle|length %}
    ...
{% endif %}
```

To loop through the selected users:

```twig
{% for user in entry.usersFieldHandle.all() %}
    ...
{% endfor %}
```

Rather than typing “`entry.usersFieldHandle`” every time, you can call it once and set it to another variable:

```twig
{% set users = entry.usersFieldHandle %}

{% if users|length %}

    <h3>Some great users</h3>
    {% for user in users %}
        ...
    {% endfor %}

{% endif %}
```

You can add parameters to the ElementCriteriaModel object as well:

```twig
{% set authors = entry.usersFieldHandle.group('authors') %}
```

If your Users field is only meant to have a single user selected, remember that calling your Users field will still give you the same ElementCriteriaModel, not the selected user. To get the first (and only) user selected, use `one()`:

```twig
{% set user = entry.myUsersField.one() %}

{% if user %}
    ...
{% endif %}
```


### See Also

* [Element Queries](element-queries.md)
* [User Query Params](element-query-params/user-query-params.md)
* [craft\elements\User](https://docs.craftcms.com/api/v3/craft-elements-user.html)
* [Relations](relations.md)
