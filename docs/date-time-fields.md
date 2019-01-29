# Date/Time Fields

Date fields give you a date picker, and optionally a time picker as well.

## Settings

Date/Time fields let you choose whether you want to show the date, the time, or both.

## Templating

### Querying Elements with Checkboxes Fields

When [querying for elements](dev/element-queries/README.md) that have a Date/Time field, you can filter the results based on the Date/Time field data using a query param named after your field’s handle.

Possible values include:

| Value | Fetches elements…
| - | -
| `':empty:'` | that don’t have a selected date.
| `':notempty:'` | that have a selected date.
| `'>= 2018-04-01'` | that have a date selected on or after 2018-04-01.
| `'< 2018-05-01'` | that have a date selected before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that have a date selected between 2018-04-01 and 2018-05-01.
| `['or', '< 2018-04-04', '> 2018-05-01']` | that have a date selected before 2018-04-01 or after 2018-05-01.

```twig
{# Fetch entries with with a selecetd date in the next month #}
{% set start = now|atom %}
{% set end = now|date_modify('+1 month')|atom %}

{% set entries = craft.entries()
    .<FieldHandle>('and', ">= #{start}", "< #{end}")
    .all() %}
```

::: tip
The [atom](dev/filters.md#atom) filter converts a date to an ISO-8601 timestamp.
:::

### Working with Date/Time Field Data

If you have an element with a Date/Time field in your template, you can access its value using your Date/Time field’s handle:

```twig
{% set value = entry.<FieldHandle> %}
```

That will give you a [DateTime](http://php.net/manual/en/class.datetime.php) object that represents the selected date, or `null` if no date was selected.

```twig
{% if entry.<FieldHandle> %}
    Selected date: {{ entry.<FieldHandle>|datetime('short') }}
{% endif %}
```

Craft and Twig provide several Twig filters for manipulating dates, which you can use depending on your needs:

- [date](dev/filters.md#date)
- [time](dev/filters.md#time)
- [datetime](dev/filters.md#datetime)
- [timestamp](dev/filters.md#timestamp)
- [atom](dev/filters.md#atom)
- [rss](dev/filters.md#rss)
- [date_modify](https://twig.symfony.com/doc/2.x/filters/date_modify.html)

### Saving Date/Time Fields in Entry Forms

If you have an [entry form](dev/examples/entry-form.md) that needs to contain a Date/Time field, you can create a `date` or `datetime-local` input.

If you just the user to be able to select a date, use a `date` input:

```twig
{% set currentValue = entry is defined and entry.<FieldHandle>
    ? entry.<FieldHandle>|date('Y-m-d')
    : '' %}

<input type="date" name="fields[<FieldHandle>]" value="{{ currentValue }}">
```

If you want the user to be able to select a time as well, use a `datetime-local` input:

```twig
{% set currentValue = entry is defined and entry.<FieldHandle>
    ? entry.<FieldHandle>|date('Y-m-d\\TH:i')
    : '' %}

<input type="datetime-local" name="fields[<FieldHandle>]" value="{{ currentValue }}">
```

::: tip
The [HTML5Forms.js](https://github.com/zoltan-dulac/html5Forms.js) polyfill can be used to implement `date` and `datetime-local` inputs [while we wait](https://caniuse.com/#feat=input-datetime) for better browser support.
:::

Note that Craft will assume the UTC time zone.
