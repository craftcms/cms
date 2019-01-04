# Multi-select Fields

Multi-select fields give you a multi-select input.

## Settings

Multi-select fields have the following settings:

* **Multi-select Options** – Define the options that will be available in the field. You even get to set the option values and labels separately, and choose which ones should be selected by default.

## Templating Examples

#### Loop through the selected options:

```twig
{% for option in entry.multiselectFieldHandle %}
    Label: {{ option.label }}
    Value: {{ option }} or {{ option.value }}
{% endfor %}
```

#### Loop through all of the available options:

```twig
{% for option in entry.multiselectFieldHandle.options %}
    Label:    {{ option.label }}
    Value:    {{ option }} or {{ option.value }}
    Selected: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

#### See if any options are selected:

```twig
{% if entry.multiselectFieldHandle|length %}
```

#### See if a particular option is selected:

```twig
{% if entry.multiselectFieldHandle.contains('optionValue') %}
```

#### Entry form:

```twig
{% set field = craft.app.fields.getFieldByHandle('multiselectFieldHandle') %}

{# Include a hidden input first so Craft knows to update the
   existing value, if no options are selected. #}
<input type="hidden" name="fields[multiselectFieldHandle]" value="">

<select multiple name="fields[multiselectFieldHandle][]">
    {% for option in field.options %}

        {% set selected = entry is defined
            ? entry.multiselectFieldHandle.contains(option.value)
            : option.default %}

        <option value="{{ option.value }}"
                {% if selected %}selected{% endif %}>
            {{ option.label }}
        </option>
    {% endfor %}
</select>
```
