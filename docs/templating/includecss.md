# `{% includeCss %}`

This tag will queue up a CSS snippet for inclusion on the page.

```twig
{% set myCss %}
.content {
    color: {{ entry.textColor }};
}
{% endset %}

{% includeCss myCss %}
```

## Parameters

The `{% includeCss %}` tag supports the following parameters:

### CSS snippet

A string that defines the CSS that should be included. The string can be typed directly into the tag, or you can set it to a variable beforehand, and just type the variable name.

### `first`

Add `first` at the end of the tag if you want this CSS to be included before any other CSS snippets.

```twig
{% includeCss myCss first %}
```

## Where does it get output?

Your CSS snippet will be output by the [getHeadHtml()](functions.md#getheadhtml) function. If you aren’t calling that function anywhere, Craft will insert it right before the HTML’s `</head>` tag.

