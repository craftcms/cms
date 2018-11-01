# Date/Time Fields

Date fields give you a date picker, and optionally a time picker as well.

## Settings

![datetime-settings.2x](./images/field-types/date-time/datetime-settings.2x.jpg)

Date/Time fields let you choose whether you want to show the date, the time, or both.

## The Field

Date/Time fields show either a date picker, a time picker, or both, depending on the settings.

![datetime-entry.2x](./images/field-types/date-time/datetime-entry.2x.jpg)

Dates and times are both formatted for the user’s preferred locale. They are displayed in the site’s timezone, however they get stored in UTC like all other dates in Craft.

## Templating

Calling a Date field in your templates will return a [DateTime](templating/datetime.md) variable set to the selected date, if there was one.

```twig
{% if user.birthday %}
    {{ user.name }}’s birthday is: {{ user.birthday | date('M j, Y') }}
{% endif %}
```
