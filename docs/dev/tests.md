# Tests

In addition to the template tags that [Twig comes with](https://twig.symfony.com/doc/tests/index.html), Craft provides a few of its own.

## `instance of`

Returns whether an object is an instance of another object or class.

```twig
{% if element is instance of('craft\\elements\\Entry') %}
    <h1>{{ entry.title }}</h1>
{% endif %}
```

## `missing`

Returns whether a given object is an instance of <api:craft\base\MissingComponentInterface>, an interface used to represent components whose types are missing.

```twig
{% if field is missing %}
    <p>😱</p>
{% endif %}
```
