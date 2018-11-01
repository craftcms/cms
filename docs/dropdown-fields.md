# Dropdown Fields

Dropdown fields give you a dropdown input.

## Settings

Dropdown fields have the following settings:

* **Dropdown Options** – Define the options that will be available in the field. You even get to set the option values and labels separately, and choose which one should be selected by default.

## Templating Examples

#### Output the selected option’s value:

```twig
{{ entry.dropdownFieldHandle }} or {{ entry.dropdownFieldHandle.value }}
```

#### Output the selected option’s label:

```twig
{{ entry.dropdownFieldHandle.label }}
```

#### Loop through all of the available options:

```twig
{% for option in entry.dropdownFieldHandle.options %}
    Label:    {{ option.label }}
    Value:    {{ option }} or {{ option.value }}
    Selected: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

#### Entry form:

```twig
{% set field = craft.app.fields.getFieldByHandle('dropdownFieldHandle') %}

<select name="fields[dropdownFieldHandle]">
    {% for option in field.options %}

        {% set selected = entry is defined
            ? entry.dropdownFieldHandle.value == option.value
            : option.default %}

        <option value="{{ option.value }}"
                {% if selected %}selected{% endif %}>
            {{ option.label }}
        </option>
    {% endfor %}
</select>
```
