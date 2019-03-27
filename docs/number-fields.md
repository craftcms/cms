# Number Fields

Number fields give you a text input that accepts a numeric value.

## Settings

Number fields have the following settings:

* **Default Value** – The default value that should be applied for new elements.
* **Min Value** – The lowest number that may be entered in the field
* **Max Value** – The highest number that may be entered in the field.
* **Decimal Points** – The maximum number of decimal points that may be entered in the field
* **Prefix** – Text that should be displayed before the input.
* **Suffix** – Text that should be displayed after the input.

## Templating

### Querying Elements with Number Fields

When [querying for elements](dev/element-queries/README.md) that have a Number field, you can filter the results based on the Number field data using a query param named after your field’s handle.

Possible values include:

| Value | Fetches elements…
| - | -
| `100` | with a value of 100.
| `'>= 100'` | with a value of at least 100.
| `['>= 100', '<= 1000']` | with a value between 100 and 1,000.

```twig
{# Fetch entries with a Numbber field set to at least 100 #}
{% set entries = craft.entries()
    .<FieldHandle>('>= 100')
    .all() %}
```

### Working with Number Field Data

If you have an element with a Number field in your template, you can access its data using your Number field’s handle:

```twig
{% set value = entry.<FieldHandle> %}
```

That will give you the number value for the field, or `null` if there is no value.

To format the number with proper thousands separators (e.g. `,`), use the [number](./dev/filters.md#number) filter:

```twig
{{ entry.<FieldHandle>|number }}
```

If the number will always be an integer, pass `decimals=0` to format the number without any decimals.

```twig
{{ entry.<FieldHandle>|number(decimals=0) }}
```

### Saving Number Fields in Entry Forms

If you have an [entry form](dev/examples/entry-form.md) that needs to contain a Number field, you can use this template as a starting point:

```twig
{% set field = craft.app.fields.getFieldByHandle('<FieldHandle>') %}

{% set value = entry is defined
    ? entry.<FieldHandle>
    : field.defaultValue %}

<input type="number"
    name="fields[<FieldHandle>]"
    min="{{ field.min }}"
    max="{{ field.max }}"
    value="{{ value }}">
```
