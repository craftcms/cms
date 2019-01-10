# Dropdown Fields

Dropdown fields give you a dropdown input.

## Settings

Dropdown fields have the following settings:

* **Dropdown Options** – Define the options that will be available in the field. You even get to set the option values and labels separately, and choose which one should be selected by default.

## Templating

### Querying Elements with Dropdown Fields

When [querying for elements](dev/element-queries/README.md) that have a Dropdown field, you can filter the results based on the Dropdown field data using a query param named after your field’s handle.

Possible values include:

| Value | Fetches elements…
| - | -
| `'foo'` | with a `foo` option selected.
| `'not foo'` | without a `foo` option selected.

```twig
{# Fetch entries with the 'foo' option selected #}
{% set entries = craft.entries()
    .<FieldHandle>('foo')
    .all() %}
```

### Working with Dropdown Field Data

If you have an element with a Dropdown field in your template, you can access its data using your Dropdown field’s handle:

```twig
{% set value = entry.<FieldHandle> %}
```

That will give you a <api:craft\fields\data\SingleOptionFieldData> object that contains the field data.

To show the selected option, output it as a string, or output the [value](api:craft\fields\data\SingleOptionFieldData::$value) property:

```twig
{{ entry.<FieldHandle> }} or {{ entry.<FieldHandle>.value }}
```

To see if an option is selected, use the [value](api:craft\fields\data\SingleOptionFieldData::$value) property:

```twig
{% if entry.<FieldHandle>.value %}
```

To show the selected option’s label, output the [label](api:craft\fields\data\SingleOptionFieldData::$label) property:

```twig
{{ entry.<FieldHandle>.label }}
```

To loop through all of the available options, iterate over the [options](api:craft\fields\data\SingleOptionFieldData::getOptions()) property:

```twig
{% for option in entry.<FieldHandle>.options %}
    Label:    {{ option.label }}
    Value:    {{ option }} or {{ option.value }}
    Selected: {{ option.selected ? 'Yes' : 'No' }}
{% endfor %}
```

### Saving Dropdown Fields in Entry Forms

If you have an [entry form](dev/examples/entry-form.md) that needs to contain a Dropdown field, you can use this template as a starting point:

```twig
{% set field = craft.app.fields.getFieldByHandle('<FieldHandle>') %}

<select name="fields[<FieldHandle>]">
    {% for option in field.options %}

        {% set selected = entry is defined
            ? entry.<FieldHandle>.value == option.value
            : option.default %}

        <option value="{{ option.value }}"
                {% if selected %}selected{% endif %}>
            {{ option.label }}
        </option>
    {% endfor %}
</select>
```
