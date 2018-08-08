# `{% switch %}` Tags

“Switch statements” offer a clean way to compare a variable against multiple possible values, instead of using several repetitive `{% if %}` conditionals.

Take this template for example, which is running different template code depending on a Matrix block’s type:

```twig
{% if matrixBlock.type == "text" %}

    {{ matrixBlock.textField|markdown }}

{% elseif matrixBlock.type == "image" %}

    {{ matrixBlock.image[0].getImg() }}

{% else %}

    <p>A font walks into a bar.</p>
    <p>The bartender says, “Hey, we don’t serve your type in here!”</p>

{% endif %}
```

Since all of the conditionals are evaluating the same thing – `matrixBlock.type` – we can simplify that code using a `{% switch %}` tag instead:

```twig
{% switch matrixBlock.type %}

    {% case "text" %}

        {{ matrixBlock.textField|markdown }}

    {% case "image" %}

        {{ matrixBlock.image[0].getImg() }}

    {% default %}

        <p>A font walks into a bar.</p>
        <p>The bartender says, “Hey, we don’t serve your type in here!”</p>

{% endswitch %}
```

If you’re using the `{% switch %}` tag inside of a `{% for %}` loop, you won’t be able to access Twig’s [loop variable](https://twig.symfony.com/doc/tags/for.html#the-loop-variable) directly inside of the `{% switch %}` tag.  Instead, you can access it like so:

```twig
{% for matrixBlock in entry.matrixField.all() %}
    {% set loopIndex = loop.index %}

    {% switch matrixBlock.type %}

        {% case "text" %}

            Loop #{{ loopIndex }}

    {% endswitch %}
{% endfor %}
```

Tip: This tag is a bit simpler than other languages’ `switch` implementations you may have seen: matching `cases` are automatically broken out of, so there’s no need for `break` statements.
