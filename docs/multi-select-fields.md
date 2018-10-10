# Multi-select Fields

Multi-select fields give you a multi-select input.

## Settings

![multi-select-settings.2x](./images/field-types/multi-select/multi-select-settings.2x.png)

Multi-select fields have the following settings:

- **Multi-select Options** – Define the options that will be available in the field. You even get to set the option values and labels separately, and choose which ones should be checked by default.

## The Field

Multi-select fields will show a multi-select input with each of the Multi-select Options as defined in the field settings:

![multi-select-entry.2x](./images/field-types/multi-select/multi-select-entry.2x.png)

## Templating

You can loop through your selected options like so:

```twig
<ul>
    {% for option in entry.multiselectFieldHandle %}
        <li>{{ option }}</li>
    {% endfor %}
</ul>
```

Or you can loop through all of the available options rather than just the selected ones:

```twig
<ul>
    {% for option in entry.multiselectFieldHandle.options %}
        <li>{{ option }}</li>
    {% endfor %}
</ul>
```

In either case, you can output an option’s label by typing `{{ option.label }}` instead, and you can tell if the option is selected or not via `option.selected`.

You can also tell if a particular option is selected outside the scope of looping through the options like so:

```twig
{% if entry.multiselectFieldHandle.contains('tequilla') %}
    <p>Really?</p>
{% endif %}
```
