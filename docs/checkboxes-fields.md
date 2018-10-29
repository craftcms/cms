# Checkboxes Fields

Checkboxes fields give you a group of checkboxes.

## Settings

Checkboxes fields have the following settings:

* **Checkbox Options** – Define the checkboxes that will be available in the field. You even get to set the option values and labels separately, and choose which ones should be checked by default.

## Templating Examples

#### Loop through the checked checkboxes:

```twig
{% for option in entry.checkboxFieldHandle %}
    Label: {{ option.label }}
    Value: {{ option }} or {{ option.value }}
{% endfor %}
```

#### Loop through all of the available checkboxes:

```twig
{% for option in entry.checkboxFieldHandle.options %}
    Label:   {{ option.label }}
    Value:   {{ option }} or {{ option.value }}
    Checked: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

#### See if any checkboxes are checked:

```twig
{% if entry.checkboxFieldHandle|length %}
```

#### See if a particular checkbox is checked:

```twig
{% if entry.checkboxFieldHandle.contains('optionValue') %}
```

#### Entry form:

```twig
{% set field = craft.app.fields.getFieldByHandle('checkboxFieldhandle') %}

{# Include a hidden input first so Craft knows to update the
   existing value, if no checkboxes are checked. #}
<input type="hidden" name="fields[checkboxFieldhandle]" value="">

<ul>
    {% for option in field.options %}

        {% set checked = entry is defined
            ? entry.checkboxFieldhandle.contains(option.value)
            : option.default %}

        <li><label>
            <input type="checkbox"
                name="fields[checkboxFieldHandle][]"
                value="{{ option.value }}"
                {% if checked %}checked{% endif %}>
            {{ option.label }}
        </label></li>
    {% endfor %}
</ul>
```
