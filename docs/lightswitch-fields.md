# Lightswitch Fields

Lightswitch fields give you a simple toggle input for times when all you need is a “Yes” or “No” answer.

## The Field

## Templating

This field will either return a `1` or `0` from the database, so you can test if it is in the `on` position from a template like so:

```twig
{% if entry.lightswitchFieldHandle %}
    <p>I'm on!</p>
{% else %}
    <p>I'm off.</p>
{% endif %}
```
