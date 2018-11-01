# Twig Primer

Here’s a rundown of the core concepts in Twig, the templating engine used by Craft.

## Logic Tags

Logic tags control what happens in your template. They can set variables, test conditionals, loop through arrays, and much more.

Their syntax always begins with “`{%`” and ends with “`%}`”. What happens in between is up to the tag you’re using.

```twig
<p>Is it happy hour?</p>

{% set hour = now|date("G") %}
{% if hour >= 16 and hour < 18 %}
    <p>Yes!</p>
{% else %}
    <p>Nope.</p>
{% endif %}
```

## Output Tags

Output tags are responsible for printing things out to the rendered HTML.

Their syntax always begins with “`{{`” and ends with “`}}`”. You can put just about anything inside them – as long as it can be evaluated into a string.

```twig
<p>The current time is {{ now|date("g:i a") }}.</p>
```

::: tip
You never place tags within other tags in Twig.

```twig
{# wrong #}
{% set entry = craft.entries.section( {{ sectionId }} ).first() %}
{% set entry = craft.entries.section( {% if filterBySection %} sectionId {% endif %} ) %}

{# right #}
{% set entry = craft.entries.section( sectionId ).first() %}
{% set entry = craft.entries.section( filterBySection ? sectionId : null ) %}
```
:::

Resources:

* [Tags that come with Twig](https://twig.symfony.com/doc/tags/index.html)
* [Craft’s custom tags](templating/tags.md)


## Comments

You can leave comments for future self in the code using comment tags. Comments won’t ever be evaluated or printed out; Twig will simply pretend they don’t exist.

Their syntax always begins with “`{#`” and ends with “`#}`”.

```twig
{# Holy cow Twig is awesome! #}
```


## Variables

Variables in Twig are just like variables in Javascript or any other programming language. There are different types of variables – strings, arrays, booleans, and objects. You can pass them into functions, manipulate them, and output them.

All of your Craft templates are pre-loaded with a few [global variables](templating/global-variables.md); templates that are loaded as a result of a matching [route](routing.md#dynamic-routes) get pre-loaded with the variables defined by the route’s tokens; and templates that are loaded as the result of a matching [entry](sections-and-entries.md) URL get an “entry” variable (see [routing](routing.md) for more details).


## Filters

You can manipulate variables with filters. The syntax is the variable name followed by a pipe (`|`) followed by the filter name:

```twig
{{ siteName|upper }}
```

Some filters accept parameters:

```twig
{{ now|date("M d, Y") }}
```

Resources:

* [Filters that come with Twig](https://twig.symfony.com/doc/filters/index.html)
* [Craft’s custom filters](templating/filters.md)


## Functions

Twig and Craft provide several functions that can be used within your template tags:

```twig
<h3>Watch me count to ten!</h3>
<ul>
    {% for num in range(1, 10) %}
        <li class="{{ cycle(['odd', 'even'], loop.index0) }}">
            {{ num }}
        </li>
    {% endfor %}
</ul>
```

Resources:

* [Functions that come with Twig](https://twig.symfony.com/doc/functions/index.html)
* [Craft’s custom functions](templating/functions.md)


## Continued Reading

There are several learning resources available online for learning Twig:

* [Twig for Template Designers](https://twig.symfony.com/doc/templates.html) documents all of Twig’s features in detail. It can be overly technical at times, but we still recommend you read through it.
* [Straight up Craft](https://straightupcraft.com/twig-templating) has some great articles on how to use Twig within Craft.
* [Twig for Designers](https://github.com/brandonkelly/TwigForDesigners) is an in-progress eBook that aims to explain how Twig works to non-developers.
