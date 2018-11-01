# Number Fields

Number fields give you a text input that accepts a numeric value.

## Settings

Number fields have the following settings:

* **Min Value** – The lowest number that may be entered in the field
* **Max Value** – The highest number that may be entered in the field.
* **Decimal Points** – The maximum number of decimal points that may be entered in the field


## The Field

Number fields will show a text box where you can enter a number:

## Templating

Calling a Number field in your templates will return the number that was entered in the field.

```twig
{% if user.birthyear %}
    <p>{{ user.name }} was born in {{ user.birthyear }}.</p>

    {% set age = now.year - user.birthyear %}
    <p>That makes them {{ age }} years old!</p>
{% endif %}
```
