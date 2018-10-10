# Dropdown Fields

Dropdown fields give you a dropdown input.

## Settings

![dropdown-settings.2x](./images/field-types/dropdown/dropdown-settings.2x.png)

Dropdown fields have the following settings:

- **Dropdown Options** – Define the options that will be available in the field. You even get to set the option values and labels separately, and choose which one should be selected by default.

## The Field

Dropdown fields will show a dropdown input with each of the Dropdown Options as defined in the field settings:

![dropdown-entry1.2x](./images/field-types/dropdown/dropdown-entry1.2x.png)

## Templating

You can output the selected option’s value like so:

```twig
{{ entry.dropdownFieldHandle }}
```

You can output the selected option’s label like so:

```twig
{{ entry.dropdownFieldHandle.label }}
```

Or you can loop through all of the available options rather than just the selected one:

```twig
<ul>
    {% for option in entry.dropdownFieldHandle.options %}
        <li>{{ option }}</li>
    {% endfor %}
</ul>
```

You can output an option’s label by typing `{{ option.label }}` instead, and you can tell if the option is selected or not via `option.selected`.

If you’re not directly outputting the value of the field, like assigning it to a variable for example, you will need to use `dropdownFieldHandle.value`, like so:

```twig
{% set dropdownValue = entry.dropdownFieldHandle.value %}
```
