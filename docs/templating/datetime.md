# DateTime

DateTime objects provide information about a given date/time.

::: tip
For a full list of all of the available date and time formatting symbols, see the [PHP date](http://php.net/manual/en/function.date.php) reference.
:::

## Methods

DateTime objects have the following methods:

### `atom()`

Returns the date/time formatted for an Atom feed (`Y-m-d\TH:i:sP`). (See the [atom-feed](atom-feed.md) example template.)

```twig
<published>{{ entry.postDate.atom }}</published>
```

### `cookie()`

Returns the date/time formatted for a HTTP cookie (`l, d-M-y H:i:s T`).

### `day()`

Returns the 1- or 2-digit day of the month.

### `getTimestamp()`

Returns the date formatted as a Unix timestamp.

### `localeDate()`

Returns the date formatted in the current locale’s preferred date format (`n/j/y` for `en_us`, `d/m/y` for `en_gb`, etc.).

### `localeTime()`

Returns the time formatted in the current locale’s preferred time format (`g:i A` for `en_us`, `H:i` for `en_gb`, etc.).

### `month()`

Returns the 1- or 2-digit month.

### `iso8601()`

Returns the date/time formatted in the ISO-8601 spec (`Y-m-d\TH:i:sO`).

### `rfc822()`

Returns the date/time formatted in the RFC 822 spec (`D, d M y H:i:s O`).

### `rfc850()`

Returns the date/time formatted in the RFC 850 spec (`l, d-M-y H:i:s T`).

### `rfc1036()`

Returns the date/time formatted in the RFC 1036 spec (`D, d M y H:i:s O`).

### `rfc1123()`

Returns the date/time formatted in the RFC 1123 spec (`D, d M Y H:i:s O`).

### `rfc2822()`

Returns the date/time formatted in the RFC 2822 spec (`D, d M Y H:i:s O`).

### `rfc3339()`

Returns the date/time formatted in the RFC 3339 spec (`Y-m-d\TH:i:sP`).

### `rss()`

Returns the date/time formatted for an RSS feed (`D, d M Y H:i:s O`). (See the [rss-feed](rss-feed.md) example template.)

```twig
<pubDate>{{ entry.postDate.rss }}</pubDate>
```

### `w3c()`

Returns the date/time formatted in the W3C’s spec (`Y-m-d\TH:i:sP`).

### `w3cDate()`

Returns the date formatted in the W3C’s spec (`Y-m-d`).

### `year()`

Returns the 4-digit year.

```twig
<p>Copyright {{ now.year }} Pixel &amp; Tonic, Inc.</p>
```


## See Also

You can output your date in any format you’d like using Twig’s [date](https://twig.symfony.com/doc/filters/date.html) filter:

```twig
<p>Today’s date is {{ now|date("M d, Y") }}.</p>
```

You can get a date that is relative to an existing DateTime object using Twig’s [date_modify](https://twig.symfony.com/doc/filters/date_modify.html) filter:

```twig
{% set tomorrow = now|date_modify("+1 day") %}
<p>Tomorrow’s date is {{ tomorrow|date("M d, Y") }}.</p>
```
