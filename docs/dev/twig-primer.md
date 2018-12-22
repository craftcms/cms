# Twig Primer

Here’s a rundown of the core concepts in Twig, the templating engine used by Craft. 

This is only meant as a primer, not a comprehensive documentation of everything Twig can do. 

To learn more, visit the Continued Reading section at the bottom of this page or refer directly to the [official Twig documentation](https://twig.symfony.com/doc/templates.html) .

## Three Types of Twig Tags

There are three types of tags in Twig:

* Logic Tags
* Output Tags
* Comment Tags

Let's review each one in more detail.

### Logic Tags

Logic tags control what happens in your template. They can set variables, test conditionals, loop through arrays, and much more.

Logic tags don't output anything to the template on their own.

Their syntax always begins with “`{%`” and ends with “`%}`”. What happens in between is up to the tag you’re using.

```twig
<p>Is it quitting time?</p>

{% set hour = now|date("G") %}
{% if hour >= 16 and hour < 18 %}
    <p>Yes!</p>
{% else %}
    <p>Nope.</p>
{% endif %}
```

### Output Tags

Output tags are responsible for printing things out to the rendered HTML.

Their syntax always begins with “`{{`” and ends with “`}}`”. You can put just about anything inside them – as long as Twig can evaluate it into a string.

```twig
<p>The current time is {{ now|date("g:i a") }}.</p>
```

Output tags are only for outputting to the template, so you never place output tags within statement tags in Twig.

These examples are incorrect:

```twig
{% set entry = craft.entries.section( {{ sectionId }} ).one() %}
{% set entry = craft.entries.section( {% if filterBySection %} sectionId {% endif %} ) %}
```

These are correct:

```twig
{% set entry = craft.entries.section( sectionId ).one() %}
{% set entry = craft.entries.section( filterBySection ? sectionId : null ) %}
```

Resources:

* [Tags that come with Twig](https://twig.symfony.com/doc/tags/index.html)
* [Craft’s custom tags](tags.md)


### Comment Tags

You can leave comments for future self in the code using comment tags. Twig won't evaluate anything inside the comment tags; it will simply pretend they don’t exist.

The comment syntax always begins with “`{#`” and ends with “`#}`”.

```twig
{# Loop through the recipes #}
```

Anything put inside of the comments tags will not render to the final template, not even as an HTML comment.

## Variables

Variables in Twig are just like variables in Javascript or any other programming language. There are different types of variables – strings, arrays, booleans, and objects. You can pass them into functions, manipulate them, and output them.

You can assign your own variables using the `set` tag:

```twig
{% set style = 'stirred' %}

{{ style }}
```

Additionally, all of your Craft templates are pre-loaded with a few [global variables](global-variables.md):

* Templates that are loaded as a result of a matching [route](../routing.md#dynamic-routes) get pre-loaded with the variables defined by the route’s tokens
* Templates that are loaded as the result of a matching [entry](../sections-and-entries.md) URL get an `entry` variable (see [Routing](../routing.md) for more details).


## Filters

You can manipulate variables with filters. The syntax is the variable name followed by a pipe (`|`) followed by the filter name:

```twig
{{ siteName|upper }}
```

Some filters accept parameters::

```twig
{{ now|date("M d, Y") }}
```

Resources:

* [Filters that come with Twig](https://twig.symfony.com/doc/filters/index.html)
* [Craft’s custom filters](filters.md)


## Functions

Twig and Craft provide several functions that you can use within your template tags:

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
* [Craft’s custom functions](functions.md)


## Continued Reading

There are several learning resources available online for learning Twig:

* [Twig for Template Designers](https://twig.symfony.com/doc/templates.html) documents all of Twig’s features in detail. It can be overly technical at times, but we still recommend you read through it.
* [Twig Templates in Craft](https://mijingo.com/products/screencasts/twig-templates-in-craft/) is a video course by Mijingo that aims to get you comfortable with using Twig in Craft.
* [Straight up Craft](https://straightupcraft.com/twig-templating) has some great articles on how to use Twig within Craft.
* [Twig for Designers](https://github.com/brandonkelly/TwigForDesigners) is an in-progress eBook that aims to explain how Twig works to non-developers.
