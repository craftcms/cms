# Matrix Fields

Matrix fields allow you to create multiple blocks of content within a single field.

## Settings

Matrix fields have the following settings:

* **Configuration** – This is where you configure which block types should be available to your Matrix field, and which sub-fields each of those block types should have.
* **Max Blocks** – The maximum number of blocks that can be created within the field. (Default is no limit.)

## The Field

On a fresh entry, Matrix fields will just show a group of buttons – one for each of the Block Types you created in the field’s settings.

When you click on one of those buttons, a new block will be created. The Block Type’s name will be shown in the block’s title bar, and each of the Block Type’s fields will be present within the body of the block.

You can add as many blocks to your Matrix field as you’d like (or at least as many as the field’s Max Blocks setting will allow).

Each block has a settings menu that reveals additional things you can do with the block.

If multiple blocks are selected, the Collapse/Expand, Disable/Enable, and Delete options will apply to all of the selected blocks.

You can collapse Matrix blocks by clicking the “Collapse” menu option, or by double-clicking on a block’s title bar. When a block is collapsed, its title bar will show a preview of its content, so you can still identify which block it is.

Blocks can also be reordered by dragging the “Move” icon at the end of the block’s title bar. If multiple blocks are selected, all of the selected blocks will be going along for the ride.

## Templating

### Querying Elements with Matrix Fields

When [querying for elements](dev/element-queries/README.md) that have a Matrix field, you can filter the results based on the Matrix field data using a query param named after your field’s handle.

Possible values include:

| Value | Fetches elements…
| - | -
| `':empty:'` | that don’t have any Matrix blocks.
| `':notempty:'` | that have at least one Matrix block.

```twig
{# Fetch entries with a Matrix block #}
{% set entries = craft.entries()
    .<FieldHandle>(':notempty:')
    .all() %}
```

### Working with Matrix Field Data

To output your Matrix blocks in a template, use a [for-loop](https://twig.symfony.com/doc/tags/for.html) pointed at your Matrix field:

```twig
{% for block in entry.myMatrixField.all() %}
    ...
{% endfor %}
```

All of the code you put within the for-loop will be repeated for each Matrix block in the field. The current block will get set to that `block` variable we’ve defined, and it will be a <api:craft\elements\MatrixBlock> model.

Here’s an example of what the template might look like for a Matrix field with four Block Types (Heading, Text, Image, and Quote). We can determine the current block type’s handle by checking `block.type` (<api:craft\elements\MatrixBlock::getType()>).

```twig
{% for block in entry.myMatrixField.all() %}

    {% if block.type == "heading" %}

        <h3>{{ block.heading }}</h3>

    {% elseif block.type == "text" %}

        {{ block.text|markdown }}

    {% elseif block.type == "image" %}

        {% set image = block.image.one() %}
        {% if image %}
            <img src="{{ image.getUrl('thumb') }}" width="{{ image.getWidth('thumb') }}" height="{{ image.getHeight('thumb') }}" alt="{{ image.title }}">
        {% endif %}

    {% elseif block.type == "quote" %}

        <blockquote>
            <p>{{ block.quote }}</p>
            <cite>– {{ block.cite }}</cite>
        </blockquote>

    {% endif %}

{% endfor %}
```

> This code can be simplified using the [switch](dev/tags/switch.md) tag.

### Filtering by block type

If you just want to output blocks of a certain type, you can do that by appending a ‘type’ filter to your Matrix field:

```twig
{% for block in entry.myMatrixField.type('text').all() %}
    {{ block.text|markdown }}
{% endfor %}
```

You can pass multiple block types if you want:

```twig
{% for block in entry.myMatrixField.type('text, heading').all() %}
    {% if block.type == "heading" %}
        <h3>{{ block.heading }}</h3>
    {% else %}
        {{ block.text|markdown }}
    {% endif %}
{% endfor %}
```


### Adjusting the limit

By default, your Matrix field will return the first 100 blocks. You can change that by overriding the `limit` parameter.

```twig
{% for block in entry.myMatrixField.limit(5) %}
```

If you think you might have more that 100 blocks, and you want all of them to be returned, you can also set that parameter to `null`:

```twig
{% for block in entry.myMatrixField.limit(null) %}
```


### Getting the total number of blocks

You can get the total number of blocks using the [length filter](https://twig.symfony.com/doc/filters/length.html):

```twig
{{ entry.myMatrixField|length }}
```


## See Also

* [Element Queries](dev/element-queries/README.md)
* <api:craft\elements\MatrixBlock>
