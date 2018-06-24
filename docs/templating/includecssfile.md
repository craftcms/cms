# `{% includeCssFile %}`

This tag will queue up a CSS file for inclusion on the page.

```twig
{% includeCssFile "/assets/css/layouts/" ~ entry.layout ~ ".css" %}
```

## Parameters

The `{% includeCssFile %}` tag supports the following parameters:

### CSS file

A string that defines the CSS file that should be included. The string can be typed directly into the tag, or you can set it to a variable beforehand, and just type the variable name.

### `first`

Add `first` at the end of the tag if you want this CSS file to be included before any other CSS files that were included using this tag.

```twig
{% includeCssFile myCssFile first %}
```

## Where does it get output?

A `<link>` tag that points to your CSS file will be output by the [getHeadHtml()](functions.md#getheadhtml) function. If you aren’t calling that function anywhere, Craft will insert it right before the HTML’s `</head>` tag.

