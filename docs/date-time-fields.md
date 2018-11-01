# Date/Time Fields

Date fields give you a date picker, and optionally a time picker as well.

## Settings

Date/Time fields let you choose whether you want to show the date, the time, or both.


## The Field

Date/Time fields show either a date picker, a time picker, or both, depending on the settings.

Dates and times are both formatted for the user’s preferred locale. They are displayed in the site’s timezone, however they get stored in UTC like all other dates in Craft.

## Templating

Calling a Date field in your templates will return a [DateTime](http://php.net/manual/en/class.datetime.php) object set to the selected date, or `null` if the field doesn’t have a value.

```twig
{% if user.birthday %}
    {{ user.name }}’s birthday is: {{ user.birthday|date('M j, Y') }}
{% endif %}
```
