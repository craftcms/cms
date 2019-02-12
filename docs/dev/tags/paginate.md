# `{% paginate %}` Tags

This tag makes it easy to paginate a set of elements across multiple pages.

```twig
{% paginate craft.entries.section('blog').limit(10) as pageInfo, pageEntries %}

{% for entry in pageEntries %}
    <article>
        <h1>{{ entry.title }}</h1>
        {{ entry.body }}
    </article>
{% endfor %}

{% if pageInfo.prevUrl %}<a href="{{ pageInfo.prevUrl }}">Previous Page</a>{% endif %}
{% if pageInfo.nextUrl %}<a href="{{ pageInfo.nextUrl }}">Next Page</a>{% endif %}
```

Paginated URLs will be identical to the first page’s URL, except that “/p_X_” will be appended to the end (where _X_ is the page number), e.g. `http://my-project.test/news/p2`.

::: tip
You can use the <config:pageTrigger> config setting to customize what comes before the actual page number in your URLs. For example you could set it to `'page/'`, and your paginated URLs would start looking like `http://my-project.test/news/page/2`.
:::

::: warning
Only a single `{% paginate %}` tag should be used per request.
:::

## Parameters

The `{% paginate %}` tag has the following parameters:

### Element criteria

The first thing you pass into the `{% paginate %}` tag is an [Element Query](../element-queries/README.md) object, which defines all of the elements that should be paginated. Use the `limit` parameter to define how many elements should show up per page.

Warning: This parameter needs to be an actual ElementCriteriaModel object; not an array of elements. So don’t call `all()` on the object.

### `as`

Next up you need to type “`as`”, followed by one or two variable names:

* `as pageInfo, pageEntries`
* `as pageEntries`

The actual variable name(s) are up to you, however if you only specify one variable name here, the `pageInfo` variable will be called “`paginate`” by default.

Here’s what they get set to:

* `pageInfo` gets set to a <api:craft\web\twig\variables\Paginate> object, which provides info about the current page, and some helper methods for creating links to other pages ([more details](#the-pageInfo-variable) below).
* `pageEntries` gets set to an array of the elements that belong to the current page.


## Showing the elements

The `{% paginate %}` tag won’t actually output the current page’s elements for you – it will only give you an array of the elements that should be on the current page (referenced by the variable you defined in the `as` parameter.)

Following your `{% paginate %}` tag, you will need to loop through this page’s elements using a [for](https://twig.symfony.com/doc/tags/for.html) tag.

```twig
{% paginate craft.entries.section('blog').limit(10) as pageEntries %}

{% for entry in pageEntries %}
    <article>
        <h1>{{ entry.title }}</h1>
        {{ entry.body }}
    </article>
{% endfor %}
```

## The `pageInfo` variable

The `pageInfo` variable (or whatever you’ve called it, or `paginate` by default) provides the following properties and methods:

* **`pageInfo.first`** – The offset of the first element on the current page.
* **`pageInfo.last`** – The offset of the last element on the current page.
* **`pageInfo.total`** – The total number of elements across all pages
* **`pageInfo.currentPage`** – The current page number.
* **`pageInfo.totalPages`** – The total number of pages.
* **`pageInfo.prevUrl`** – The URL to the previous page, or `null` if you’re on the first page.
* **`pageInfo.nextUrl`** – The URL to the next page, or `null` if you’re on the last page.
* **`pageInfo.firstUrl`** – The URL to the first page.
* **`pageInfo.lastUrl`** – The URL to the last page.
* **`pageInfo.getPageUrl( page )`** – Returns the URL to a given page number, or `null` if the page doesn’t exist.
* **`pageInfo.getPrevUrls( [dist] )`** – Returns an array of URLs to the previous pages, with keys set to the page numbers. The URLs are returned in ascending order. You can optionally pass in the maximum distance away from the current page the function should go.
* **`pageInfo.getNextUrls( [dist] )`** – Returns an array of URLs to the next pages, with keys set to the page numbers. The URLs are returned in ascending order. You can optionally pass in the maximum distance away from the current page the function should go.
* **`pageInfo.getRangeUrls( start, end )`** – Returns an array of URLs to pages in a given range of page numbers, with keys set to the page numbers.


## Navigation examples

The [pageInfo](#the-pageInfo-variable) variable gives you lots of options for building the pagination navigation that’s right for you. Here are a few common examples.

### Previous/Next Page Links

If you just want simple Previous Page and Next Page links to appear, you can do this:

```twig
{% paginate craft.entries.section('blog').limit(10) as pageInfo, pageEntries %}

{% if pageInfo.prevUrl %}<a href="{{ pageInfo.prevUrl }}">Previous Page</a>{% endif %}
{% if pageInfo.nextUrl %}<a href="{{ pageInfo.nextUrl }}">Next Page</a>{% endif %}
```

Note that we’re wrapping those links in conditionals because there won’t always be a previous or next page.

### First/Last Page Links

You can add First Page and Last Page links into the mix, you can do that too:

```twig
{% paginate craft.entries.section('blog').limit(10) as pageInfo, pageEntries %}

<a href="{{ pageInfo.firstUrl }}">First Page</a>
{% if pageInfo.prevUrl %}<a href="{{ pageInfo.prevUrl }}">Previous Page</a>{% endif %}
{% if pageInfo.nextUrl %}<a href="{{ pageInfo.nextUrl }}">Next Page</a>{% endif %}
<a href="{{ pageInfo.lastUrl }}">Last Page</a>
```

There’s no reason to wrap those links in conditionals since there will always be a first and last page.

### Nearby Page Links

If you want to create a list of nearby pages, perhaps surrounding the current page number, you can do that too!

```twig
{% paginate craft.entries.section('blog').limit(10) as pageInfo, pageEntries %}

<a href="{{ pageInfo.firstUrl }}">First Page</a>
{% if pageInfo.prevUrl %}<a href="{{ pageInfo.prevUrl }}">Previous Page</a>{% endif %}

{% for page, url in pageInfo.getPrevUrls(5) %}
    <a href="{{ url }}">{{ page }}</a>
{% endfor %}

<span class="current">{{ pageInfo.currentPage }}</span>

{% for page, url in pageInfo.getNextUrls(5) %}
    <a href="{{ url }}">{{ page }}</a>
{% endfor %}

{% if pageInfo.nextUrl %}<a href="{{ pageInfo.nextUrl }}">Next Page</a>{% endif %}
<a href="{{ pageInfo.lastUrl }}">Last Page</a>
```

In this example we’re only showing up to five page links in either direction of the current page. If you’d prefer to show more or less, just change the numbers that are passed into `getPrevUrls()` and `getNextUrls()`. You can also choose to not pass any number in at all, in which case *all* previous/next page URLs will be returned.
