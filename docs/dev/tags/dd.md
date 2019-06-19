# `{% dd %}` Tags

This tag will dump a variable out to the browser and then end the request. (`dd` stands for “Dump-and-Die”.)

```twig
{% set entry = craft.entries.id(entryId).one() %}
{% dd entry %}
```
