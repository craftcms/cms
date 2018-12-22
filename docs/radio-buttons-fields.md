# Radio Buttons Fields

Radio Buttons fields give you a group of radio buttons.

## Settings

Radio Buttons fields have the following settings:

* **Radio Button Options** – Define the radio buttons that will be available in the field. You even get to set the option values and labels separately, and choose which one should be selected by default.

## Templating Examples

#### Output the selected radio button’s value:

```twig
{{ entry.radioFieldHandle }} or {{ entry.radioFieldHandle.value }}
```

#### Output the selected radio button’s label:

```twig
{{ entry.radioFieldHandle.label }}
```

#### Loop through all of the available radio buttons:

```twig
{% for option in entry.radioFieldHandle.options %}
    Label:    {{ option.label }}
    Value:    {{ option }} or {{ option.value }}
    Selected: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

#### Entry form:

```twig
{% set field = craft.app.fields.getFieldByHandle('radioFieldhandle') %}

<ul>
    {% for option in field.options %}

        {% set selected = entry is defined
            ? entry.radioFieldHandle.value == option.value
            : option.default %}

        <li><label>
            <input type="radio"
                name="fields[radioFieldHandle]"
                value="{{ option.value }}"
                {% if selected %}checked{% endif %}>
            {{ option.label }}
        </label></li>
    {% endfor %}
</ul>
```
