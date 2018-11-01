# `craft.request`

You can get all sorts of info about the current request from `craft.request`.

## Properties

The following properties are available:

### `firstSegment`

Alias of [getFirstSegment()](#getfirstsegment).

### `isAjax`

Whether the current request is an Ajax request.

### `isLivePreview`

Whether the current request is a Live Preview request.

```twig
{% if not craft.request.isLivePreview %}
    <script type="text/javascript">
        // Google Analytics tracking code
    </script>
{% endif %}
```

### `isSecure`

Whether the current request is over SSL.

### `lastSegment`

Alias of [getLastSegment()](#getlastsegment).

### `pageNum`

Alias of [getPageNum()](#getpagenum).

### `path`

Alias of [getPath()](#getpath).

### `queryString`

Alias of [getQueryString()](#getquerystring).

### `queryStringWithoutPath`

Alias of [getQueryStringWithoutPath()](#getquerystringwithoutpath).

### `segments`

Alias of [getSegments()](#getsegments).

### `serverName`

Alias of [getServerName()](#getservername).

### `url`

Alias of [getUrl()](#geturl).

### `urlReferrer`

Alias of [getUrlReferrer()](#geturlreferrer).



## Methods

The following methods are available:

### `isMobileBrowser()`

Whether the current request is coming from a mobile browser. Pass in `true` if you want to consider tablets as mobile.

### `getCookie( name )`

Returns a cookie with the given name if it exists. If the cookie was set in JavaScript, this method will not work because all cookies in Craft go through some validation to ensure they weren’t tampered with.

### `getFirstSegment()`

Returns the first path segment in the URL.

### `getLastSegment()`

Returns the last path segment in the URL.

### `getPageNum()`

Returns the current pagination page number.

### `getParam( name )`

Returns a parameter from either the query string or POST data.

### `getPath()`

Returns the full path in the URL.

### `getPost( name )`

Returns a parameter from the POST data.

### `getSegment( n )`

Returns the *nth* path segment in the URL. If you pass a negative number, the *nth*-to-last segment will be returned instead.

```twig
The second URL segment is {{ craft.request.getSegment(2) }}.
The second-to-last URL segment is {{ craft.request.getSegment(-2) }}.
```

### `getSegments()`

Returns an array of the path segments in the URL.

### `getServerName()`

Returns the server/domain name.

### `getUserAgent()`

Returns the user agent string or null if not present.

### `getQuery( name )`

Returns a parameter from the query string.

### `getQueryString()`

Returns the full query string.

### `getQueryStringWithoutPath()`

Returns the query string, except for the `p=` param (which was probably added by your .htaccess redirect).

```twig
<a href="{{ paginate.nextUrl }}?{{ craft.request.getQueryStringWithoutPath() }}">Next Page</a>
```

### `getUrl()`

Returns the full URL for the current request.

::: tip
By the time the request makes it to Craft, the _actual_ URL will be whatever your .htaccess file has redirected the request to behind the scenes, e.g. http://example.com/index.php?p=some/path. So rather than returning the actual URL, `getUrl()` returns what the URL probably looks like to the browser. It’s really just a shortcut for calling [url()](functions.md#url) and passing in [craft.request.path](#path).

```twig
{{ url(craft.request.path) }}
```
:::

### `getUrlReferrer()`

Returns the request’s HTTP_REFERER header, if there was one.
