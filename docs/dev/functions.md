# Functions

The following functions are available to Twig templates in Craft:

## `actionInput`

A shortcut for outputting a hidden input used to route a POST request to a particular controller and action. This is effectively the same as writing `<input type="hidden" name="action" value="controller/action-name">` directly into a template.

```twig
<form method="POST">
    {{ actionInput('users/save-user') }}
    <!-- ... -->
</form>
```

## `alias`

Passes a string through [Craft::getAlias()](api:yii\BaseYii::getAlias()), which will check if the string begins with an [alias](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases). (See [Configuration](../config/README.md#aliases) for more info.)

```twig
<img src="{{ alias('@assetBaseUrl/images/logo.png') }}">
```

### `attr`

Generates a list of HTML attributes based on the given object, using <api:yii\helpers\BaseHtml::renderTagAttributes()>.

```twig
{% set myAttributes = {
    class: ['one', 'two'],
    disabled: true,
    readonly: false,
    data: {
        baz: 'Escape this "',
        qux: {
            some: ['data', '"quoted"']
        }
    },
    style: {
        'background-color': 'red',
        'font-size': '20px'
    },
} %}

<div {{ attr(myAttributes) }}></div>
```

## `attribute`

Accesses a dynamic attribute of a variable.

This works identically to Twig’s core [`attribute`](https://twig.symfony.com/doc/2.x/functions/attribute.html) function.

## `beginBody`

Outputs any scripts and styles that were registered for the “begin body” position. It should be placed right after your `<body>` tag.

```twig
<body>
    {{ beginBody() }}

    <h1>{{ page.name }}</h1>
    {{ page.body }}
</body>
```

## `block`

Prints a block’s output.

This works identically to Twig’s core [`block`](https://twig.symfony.com/doc/2.x/functions/block.html) function.

## `ceil`

Rounds a number up.

```twig
{{ ceil(42.1) }}
{# Output: 43 #}
```

## `className`

Returns the fully qualified class name of a given object.

```twig
{% set class = className(entry) %}
{# Result: 'craft\\elements\\Entry' #}
```

## `clone`

Clones a given object.

```twig
{% set query = craft.entries.section('news') %}
{% set articles = clone(query).type('articles') %}
```

## `constant`

Returns the constant value for a given string.

This works identically to Twig’s core [`constant`](https://twig.symfony.com/doc/2.x/functions/constant.html) function.

## `create`

Creates a new object instance based on a given class name or object configuration. See <api:Yii::createObject()> for a full explanation of supported arguments.

```twig
{# Pass in a class name #}
{% set cookie = create('yii\\web\\Cookie') %}

{# Or a full object configuration array #}
{% set cookie = create({
    class: 'yii\\web\\cookie',
    name: 'foo',
    value: 'bar'
}) %}
```

## `csrfInput`

Returns a hidden CSRF Token input. All sites that have CSRF Protection enabled must include this in each form that submits via POST.

```twig
<form method="post">
    {{ csrfInput() }}
    <!-- ... -->
</form>
```

## `cycle`

Cycles on an array of values.

This works identically to Twig’s core [`cycle`](https://twig.symfony.com/doc/2.x/functions/cycle.html) function.

## `date`

Converts an argument to a date.

This works identically to Twig’s core [`date`](https://twig.symfony.com/doc/2.x/functions/date.html) function.

## `dump`

Dumps information about a template variable.

This works identically to Twig’s core [`dump`](https://twig.symfony.com/doc/2.x/functions/dump.html) function.

## `endBody`

Outputs any scripts and styles that were registered for the “end body” position. It should be placed right before your `</body>` tag.

```twig
<body>
    <h1>{{ page.name }}</h1>
    {{ page.body }}

    {{ endBody() }}
</body>
```

## `expression`

Creates and returns a new <api:yii\db\Expression> object, for use in database queries.

```twig
{% set entries = craft.entries()
    .andWhere(expression('[[authorId]] = :authorId', {authorId: currentUser.id}))
    .all() %}
```

## `floor`

Rounds a number down.

```twig
{{ floor(42.9) }}
{# Output: 42 #}
```

## `getenv`

Returns the value of an environment variable.

```twig
{{ getenv('MAPS_API_KEY') }}
```

## `parseEnv`

Checks if a string references an environment variable (`$VARIABLE_NAME`) and/or an alias (`@aliasName`), and returns the referenced value.

## `head`

Outputs any scripts and styles that were registered for the “head” position. It should be placed right before your `</head>` tag.

```twig
<head>
    <title>{{ siteName }}</title>
    {{ head() }}
</head>
```

## `include`

Returns the rendered content of a template.

This works identically to Twig’s core [`include`](https://twig.symfony.com/doc/2.x/functions/include.html) function.

## `max`

Returns the biggest value in an array.

This works identically to Twig’s core [`max`](https://twig.symfony.com/doc/2.x/functions/max.html) function.

## `min`

Returns the lowest value in an array.

This works identically to Twig’s core [`min`](https://twig.symfony.com/doc/2.x/functions/min.html) function.

## `parent`

Returns the parent block’s output.

This works identically to Twig’s core [`parent`](https://twig.symfony.com/doc/2.x/functions/parent.html) function.

## `plugin`

Returns a plugin instance by its handle, or `null` if no plugin is installed and enabled with that handle.

```twig
{{ plugin('commerce').version }}
```

## `random`

Returns a random value.

This works identically to Twig’s core [`random`](https://twig.symfony.com/doc/2.x/functions/random.html) function.

## `range`

Returns a list containing an arithmetic progression of integers.

This works identically to Twig’s core [`range`](https://twig.symfony.com/doc/2.x/functions/range.html) function.

## `redirectInput`

Shortcut for typing `<input type="hidden" name="redirect" value="{{ url|hash }}">`.

```twig
{{ redirectInput(url) }}
```

## `seq`

Outputs the next or current number in a sequence, defined by `name`:

```twig
<p>This entry has been read {{ seq('hits:' ~ entry.id) }} times.</p>
```

Each time the function is called, the given sequence will be automatically incremented.

You can optionally have the number be zero-padded to a certain length.

```twig
{{ now|date('Y') ~ '-' ~ seq('orderNumber:' ~ now|date('Y'), 5) }}
{# outputs: 2018-00001 #}
```

To view the current number in the sequence without incrementing it, set the `next` argument to `false`.

```twig
<h5><a href="{{ entry.url }}">{{ entry.title }}</a></h5>
<p>{{ seq('hits:' ~ entry.id, next=false) }} views</p>
```

## `shuffle`

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

## `siteUrl`

Similar to [url()](#url-path-params-scheme-mustshowscriptname), except _only_ for creating URLs to pages on your site.

```twig
<a href="{{ siteUrl('company/contact') }}">Contact Us</a>
```

### Arguments

The `siteUrl()` function has the following arguments:

* **`path`** – The path that the resulting URL should point to on your site. It will be appended to your base site URL.
* **`params`** – Any query string parameters that should be appended to the URL. This can be either a string (e.g. `'foo=1&bar=2'`) or an object (e.g. `{foo:'1', bar:'2'}`).
* **`scheme`** – Which scheme the URL should use (`'http'` or `'https'`). The default value depends on whether the current request is served over SSL or not. If not, then the scheme in your Site URL will be used; if so, then `https` will be used.
* **`siteId`** – The ID of the site that the URL should point to. By default the current site will be used.

## `svg`

Outputs an SVG document.

You can pass the following things into it:

- An SVG file path.

  ```twig
  {{ svg('@webroot/icons/lemon.svg') }}
  ```

- A <api:craft\elements\Asset> object, such as one pulled in from an [Assets field](../assets-fields.md).

  ```twig
  {% set image = entry.myAssetsField.one() %}
  {% if image and image.extension == 'svg' %}
    {{ svg(image) }}
  {% endif %}
  ```

- Raw SVG markup.

  ```twig
  {% set image = include('_includes/icons/lemon.svg') %}
  {{ svg(image) }}
  ```

By default, if you pass an asset or raw markup into the function, the SVG will be sanitized of potentially malicious scripts using [svg-sanitizer](https://github.com/darylldoyle/svg-sanitizer), and any IDs or class names within the document will be namespaced so they don’t conflict with other IDs or class names in the DOM. You can disable those behaviors using the `sanitize` and `namespace` arguments:

```twig
{{ svg(image, sanitize=false, namespace=false) }}
```

You can also specify a custom class name that should be added to the root `<svg>` node using the `class` argument:

```twig
{{ svg('@webroot/icons/lemon.svg', class='lemon-icon') }}
```

## `source`

Returns the content of a template without rendering it.

This works identically to Twig’s core [`source`](https://twig.symfony.com/doc/2.x/functions/source.html) function.

## `template_from_string`

Loads a template from a string.

This works identically to Twig’s core [`template_from_string`](https://twig.symfony.com/doc/2.x/functions/template_from_string.html) function.

## `url`

Returns a URL.

```twig
<a href="{{ url('company/contact') }}">Contact Us</a>
```

### Arguments

The `url()` function has the following arguments:

* **`path`** – The path that the resulting URL should point to on your site. It will be appended to your base site URL.
* **`params`** – Any query string parameters that should be appended to the URL. This can be either a string (e.g. `'foo=1&bar=2'`) or an object (e.g. `{foo:'1', bar:'2'}`).
* **`scheme`** – Which scheme the URL should use (`'http'` or `'https'`). The default value depends on whether the current request is served over SSL or not. If not, then the scheme in your Site URL will be used; if so, then `https` will be used.
* **`mustShowScriptName`** – If this is set to `true`, then the URL returned will include “index.php”, disregarding the <config:omitScriptNameInUrls> config setting. (This can be useful if the URL will be used by POST requests over Ajax, where the URL will not be shown in the browser’s address bar, and you want to avoid a possible collision with your site’s .htaccess file redirect.)

::: tip
You can use the `url()` function for appending query string parameters and/or enforcing a scheme on an absolute URL:
```twig
{{ url('http://my-project.com', 'foo=1', 'https') }}
{# Outputs: "https://my-project.com?foo=1" #}
```
:::
