# Functions

On top of the template functions that [Twig comes with](http://twig.sensiolabs.org/doc/functions/index.html), Craft provides a few of its own:


## `ceil( num )`

Rounds a number up.

```twig
{{ ceil(42.1) }} => 43
```

## `floor( num )`

Rounds a number down.

```twig
{{ floor(42.9) }} => 42
```

## `getCsrfInput()`

Returns a hidden CSRF Token input. All sites that have [CSRF Protection](https://craftcms.com/support/csrf-protection) enabled must include this in each form that submits via POST.

```twig
<form method="post">
    {{ getCsrfInput() }}
    <!-- ... -->
</form>
```

## `getFootHtml()`

Outputs all of the HTML nodes that have been queued up by the [includeJsFile](tags.md#includeJsFile) and [includeJs](tags.md#includeJs) tags. It should be placed right before your `</body>` tag.

```twig
<body>
    <h1>{{ page.name }}</h1>
    {{ page.body }}

    {{ getFootHtml() }}
</body>
```

## `getHeadHtml()`

Outputs all of the HTML nodes that have been queued up by the [includeCssFile](tags.md#includeCssFile), [includeCss](tags.md#includeCss), and [includeHiResCss](tags.md#includeHiResCss) tags.

```twig
<head>
    <title>{{ siteName }}</title>
    {{ getHeadHtml() }}
</head>
```

## `max( num1, num2, ... )`

Returns the largest number of a given set.

```twig
{{ max(1, 2, 3) }} => 3
```

## `min( num1, num2, ... )`

Returns the smallest number of a given set.

```twig
{{ min(1, 2, 3) }} => 1
```

## `round( num )`

Rounds off a number to the closest integer.

```twig
{{ round(42.1) }} => 42
{{ round(42.9) }} => 43
```

## `shuffle( array )`

Randomizes the order of the elements within an array.

```twig
{% set promos = shuffle(homepage.promos) %}

{% for promo in promos %}
    <div class="promo {{ promo.slug }}">
        <h3>{{ promo.title }}</h3>
        <p>{{ promo.description }}</p>
        <a class="cta" href="{{ promo.ctaUrl }}">{{ promo.ctaLabel }}</a>
    </div>
{% endfor %}
```

## `url( path, params, protocol, mustShowScriptName )`

Returns a URL to a page on your site.

```twig
<a href="{{ url('company/contact') }}">Contact Us</a>
```

### Arguments

The `url()` function has the following arguments:

* **`path`** – The path that the resulting URL should point to on your site. It will be appended to your [Site URL](https://craftcms.com/support/site-url).
* **`params`** – Any query string parameters that should be appended to the URL. This can be either a string (e.g. `'foo=1&bar=2'`) or an object (e.g. `{foo:'1', bar:'2'}`).
* **`protocol`** – Which protocol the URL should use (`'http'` or `'https'`). The default value depends on whether the current request is served over SSL or not. If not, then the protocol in your Site URL will be used; if so, then `https` will be used.
* **`mustShowScriptName`** – If this is set to `true`, then the URL returned will include “index.php”, disregarding the [omitScriptNameInUrls](../config-settings.md#omitScriptNameInUrls) config setting. (This can be useful if the URL will be used by POST requests over Ajax, where the URL will not be shown in the browser’s address bar, and you want to avoid a possible collision with your site’s .htaccess file redirect.)

> {tip} You can also use the `url()` function for appending query string parameters and/or enforcing a protocol on an absolute URL:
> ```twig
> {{ url('http://example.com', 'foo=1', 'https') }}
> {# Outputs: "https://example.com?foo=1" #}
> ```
