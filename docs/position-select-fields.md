# Position Select Fields

Position Select fields give you a button group with various positioning options, which can be used to define how elements should be positioned in your templates.

## Settings

![position-select-settings.2x](./images/field-types/position-select/position-select-settings.2x.png)

Position Select fields have the following settings:

- **Options** – Choose which options should be available in the field. Your choices include `left`, `center`, `right`, `full`, `drop-left`, and `drop-right`. (The names are not customizable.)

## The Field

Position Select fields will show a button group with icons representing each of the options you chose in the field’s settings.

![position-select-input.2x](./images/field-types/position-select/position-select-input.2x.png)

## Templating

If you want to output the value directly, such as into a `<div>`’s `class=` attribute, you can do that like so:

```twig
<div class="block {{ entry.positionSelectFieldHandle }}">
```

You can also access the option’s value in conditionals:

```twig
{% if entry.positionSelectFieldHandle in ['left', 'right'] %}
    {# ... #}
{% elseif entry.positionSelectFieldHandle == 'center' %}
    {# ... #}
{% endif %}
```

Or you can feed it into a [switch](templating/switch.md) tag:

```twig
{% switch entry.positionSelectFieldHandle %}
    {% case 'left' %}
        {# ... #}
    {% case 'right' %}
        {# ... #}
    {% case 'center' %}
        {# ... #}
{% endswitch %}
```
