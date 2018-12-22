# `{% nav %}` Tags

This tag helps create a hierarchical navigation menu for entries in a [Structure section](../../sections-and-entries.md#section-types) or a [Category Group](../../categories.md).


```twig
{% set entries = craft.entries.section('pages').all() %}

<ul id="nav">
    {% nav entry in entries %}
        <li>
            <a href="{{ entry.url }}">{{ entry.title }}</a>
            {% ifchildren %}
                <ul>
                    {% children %}
                </ul>
            {% endifchildren %}
        </li>
    {% endnav %}
</ul>
```

## Parameters

The `{% nav %}` tag has the following parameters:

### Item name

The first thing to follow “`{% nav`” is the variable name you’d like to use to represent each item in the loop, e.g. `item`, `entry`, or `category`. You will be using this variable name to reference the items inside the loop.

### `in`

Next you need to type the word “`in`”, followed by the array of entries the tag should loop through. This can be an actual array, or an [ElementCriteriaModel]() object.

Warning: The `{% nav %}` tag requires elements to be queried in a specific (hierarchical) order, so make sure you don’t override the `order` criteria parameter in conjunction with this tag.

## Showing children

To show the children of the current element in the loop, use the `{% children %}` tag. When Craft gets to this tag, it will loop through the element’s children, applying the same template defined between your `{% nav %}` and `{% endnav %}` tags to those children.

If you want to show some additional HTML surrounding the children, but only in the event that the element actually has children, wrap your `{% children %}` tag with `{% ifchildren %}` and `{% endifchildren %}` tags.

Tip: The `{% nav %}` tag should _only_ be used in times when you want to show elements in a hierarchy, and you want the DOM to express that hierarchy. If you want to loop through elements linearly, use Twig’s [for](https://twig.symfony.com/doc/tags/for.html) tag instead.
