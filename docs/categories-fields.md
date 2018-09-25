# Categories Fields

Categories fields allow you to relate [categories](categories.md) to other elements.

## Settings

Categories fields have the following settings:

- **Source** – Which category group (or other category index source) the field should be able to relate categories from.
- **Branch Limit** – The maximum number of category tree branches that can be related with the field at once. (Default is no limit.)

  For example, if you have the following category group:
  
  ```
  Food
  ├── Fruit
  │   ├── Apples
  │   ├── Bananas
  │   └── Oranges
  └── Vegetables
      ├── Brussels sprouts
      ├── Carrots
      └── Celery
  ```

  …and Branch Limit was set to `1`, you would be able to relate Fruit, Vegetables, or one of their descendants, but no more than that.

- **Selection Label** – The label that should be used on the field’s selection button.

### Multi-Site Settings

On multi-site installs, the following settings will also be available (under “Advanced”):

- **Relate categories from a specific site?** – Whether to only allow relations to categories from a specific site.

  If enabled, a new setting will appear where you can choose which site.
  
  If disabled, related categories will always be pulled from the current site.

- **Manage relations on a per-site basis** – Whether each site should get its own set of related categories.

## The Field

Categories fields list all of the currently-related categories, with a button to select new ones.

Clicking the “Add a category” button will bring up a modal window where you can find and select additional categories. You can create new categories from this modal as well, by clicking the “New category” button.

When you select a nested category, all of the ancestors leading up to that category will also automatically be related. Likewise, when you remove a category from within the main field input, any of its descendants will also be removed.

### Inline Category Editing

When you double-click on a related category, a HUD will appear where you can edit the category’s title and custom fields.

## Templating

If you have an element with a Categories field in your template, you can access its related categories using your Categories field’s handle:

```twig
{% set relatedCategories = entry.<FieldHandle> %}
```

That will give you a [category query](dev/element-queries/category-queries.md), prepped to output all of the related categories for the given field.

### Examples

To loop through all of the related categories as a flat list, call [all()](api:craft\db\Query::all()) and then loop over the results:

```twig
{% set relatedCategories = entry.<FieldHandle>.all() %}
{% if relatedCategories|length %}
    <ul>
        {% for rel in relatedCategories %}
            <li><a href="{{ rel.url }}">{{ rel.title }}</a></li>
        {% endfor %}
    </ul>
{% endif %}
```

Or you can show them as a hierarchical list with the [nav](dev/tags/nav.md) tag:

```twig
{% set relatedCategories = entry.<FieldHandle.all() %}
{% if relatedCategories|length %}
    <ul>
        {% nav rel in relatedCategories %}
            <li>
                <a href="{{ rel.url }}">{{ rel.title }}</a>
                {% ifchildren %}
                    <ul>
                        {% children %}
                    </ul>
                {% endifchildren %}
            </li>
        {% endnav %}
    </ul>
{% endif %}
```

If you only want the first related category, call [one()](api:craft\db\Query::one()) instead, and then make sure it returned something:

```twig
{% set rel = entry.<FieldHandle>.one() %}
{% if rel %}
    <p><a href="{{ rel.url }}">{{ rel.title }}</a></p>
{% endif %}
```

If you just need to check if there are any related categories (but don’t need to fetch them), you can call [exists()](api:craft\db\Query::exists()):

```twig
{% if entry.<FieldHandle>.exists() %}
    <p>There are related categories!</p>
{% endif %}
```

You can set [parameters](dev/element-queries/category-queries.md#parameters) on the category query as well. For example, to only fetch the “leaves” (categories without any children), set the [leaves](dev/element-queries/category-queries.md#leaves) param:

```twig
{% set relatedCategories = entry.<FieldHandle>
    .leaves()
    .all() %}
```

### See Also

* [Category Queries](dev/element-queries/category-queries.md)
* <api:craft\elements\Category>
* [Relations](relations.md)
