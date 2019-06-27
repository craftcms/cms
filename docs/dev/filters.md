# Filters

The following filters are available to Twig templates in Craft:

## `abs`

Returns an absolute value.

This works identically to Twig’s core [`abs`](https://twig.symfony.com/doc/2.x/filters/abs.html) filter.

## `ascii`

Converts a string to ASCII characters.

```twig
{{ 'über'|ascii }}
{# Output: uber #}
```

By default, the current site’s language will be used when choosing ASCII character mappings. You can override that by passing in a different locale ID.

```twig
{{ 'über'|ascii('de') }}
{# Output: ueber #}
```

## `atom`

Converts a date to an ISO-8601 timestamp (e.g. `2019-01-29T10:00:00-08:00`), which should be used for Atom feeds, among other things.

```twig
{{ entry.postDate|atom }}
```

## `batch`

“Batches” items by returning a list of lists with the given number of items

This works identically to Twig’s core [`batch`](https://twig.symfony.com/doc/2.x/filters/batch.html) filter.

## `camel`

Returns a string formatted in “camelCase”.

```twig
{{ 'foo bar'|camel }}
{# Output: fooBar #}
```

## `capitalize`

Capitalizes a value.

This works identically to Twig’s core [`capitalize`](https://twig.symfony.com/doc/2.x/filters/capitalize.html) filter.

## `column`

Returns the values from a single column in the input array.

```twig
{% set entryIds = entries|column('id') %}
```

This works similarly to Twig’s core [`column`](https://twig.symfony.com/doc/2.x/filters/column.html) filter, except that [ArrayHelper::getColumn()](api:yii\helpers\BaseArrayHelper::getColumn()) is used rather than PHP’s [array_column()](https://secure.php.net/array_column) function.

## `convert_encoding`

Converts a string from one encoding to another.

This works identically to Twig’s core [`convert_encoding`](https://twig.symfony.com/doc/2.x/filters/convert_encoding.html) filter.

## `currency`

Formats a number with a given currency according to the user’s preferred language.

```twig
{{ 1000000|currency('USD') }}
{# Output: $1,000,000.00 #}
```

You can pass `stripZeros=true` to remove any fraction digits if the value to be formatted has no minor value (e.g. cents):

```twig
{{ 1000000|currency('USD', stripZeros=true) }}
{# Output: $1,000,000 #}
```

## `date`

Formats a timestamp or [DateTime](http://php.net/manual/en/class.datetime.php) object.

```twig
{{ entry.postDate|date }}
{# Output: Dec 20, 1990 #}
```

You can customize how the date is presented by passing a custom date format, just like Twig’s core [`date`](https://twig.symfony.com/doc/2.x/filters/date.html) filter:

```twig
{{ 'now'|date('m/d/Y') }}
{# Output: 12/20/1990 #}
```

Craft also provides some special format keywords that will output locale-specific date formats:

| Format               | Example                     |
| -------------------- | --------------------------- |
| `short`              | 12/20/1990                  |
| `medium` _(default)_ | Dec 20, 1990                |
| `long`               | December 20, 1990           |
| `full`               | Thursday, December 20, 1990 |

```twig
{{ entry.postDate|date('short') }}
{# Output: 12/20/1990 #}
```

The current application locale will be used by default. If you want to format the date for a different locale, use the `locale` argument:

```twig
{{ entry.postDate|date('short', locale='en-GB') }}
{# Output: 20/12/1990 #}
```

You can customize the timezone the time is output in, using the `timezone` param:

```twig
{{ entry.postDate|date('short', timezone='UTC') }}
{# Output: 12/21/1990 #}
```

## `date_modify`

Modifies a date with a given modifier string.

This works identically to Twig’s core [`date_modify`](https://twig.symfony.com/doc/2.x/filters/date_modify.html) filter.

## `datetime`

Formats a timestamp or [DateTime](http://php.net/manual/en/class.datetime.php) object, including the time of day.

```twig
{{ entry.postDate|datetime }}
{# Output: Dec 20, 1990, 5:00:00 PM #}
```

Craft provides some special format keywords that will output locale-specific date and time formats:

```twig
{{ entry.postDate|datetime('short') }}
{# Output: 9/26/2018, 5:00 PM #}
```

Possible `format` values are:

| Format               | Example                                        |
| -------------------- | ---------------------------------------------- |
| `short`              | 12/20/1990, 5:00 PM                            |
| `medium` _(default)_ | Dec 20, 1990, 5:00:00 PM                       |
| `long`               | December 20, 1990 at 5:00:00 PM PDT            |
| `full`               | Thursday, December 20, 19909 at 5:00:00 PM PDT |

The current application locale will be used by default. If you want to format the date and time for a different locale, use the `locale` argument:

```twig
{{ entry.postDate|datetime('short', locale='en-GB') }}
{# Output: 20/12/1990, 17:00 #}
```

You can customize the timezone the time is output in, using the `timezone` param:

```twig
{{ entry.postDate|datetime('short', timezone='UTC') }}
{# Output: 12/21/1990, 12:00 AM #}
```

## `default`

Returns the passed default value if the value is undefined or empty, otherwise the value of the variable.

This works identically to Twig’s core [`default`](https://twig.symfony.com/doc/2.x/filters/default.html) filter.

## `duration`

Runs a [DateInterval](http://php.net/manual/en/class.dateinterval.php) object through <api:craft\helpers\DateTimeHelper::humanDurationFromInterval()>

```twig
<p>Posted {{ entry.postDate.diff(now)|duration(false) }} ago.</p>
```

## `encenc`

Encrypts and base64-encodes a string.

```twig
{{ 'secure-string'|encenc }}
```

## `escape`

Escapes a string using strategies that depend on the context.

This works identically to Twig’s core [`escape`](https://twig.symfony.com/doc/2.x/filters/escape.html) filter.

## `filesize`

Formats a number of bytes into something nicer.

## `filter`

Filters elements of an array.

If nothing is passed to it, any “empty” elements will be removed.

```twig
{% set array = ['foo', '', 'bar', '', 'baz'] %}
{% set filteredArray = array|filter %}
{# Result: ['foo', 'bar', 'baz'] #}
```

When an arrow function is passed, this works identically to Twig’s core [`filter`](https://twig.symfony.com/doc/2.x/filters/filter.html) filter.

```twig
{% set array = ['foo', 'bar', 'baz'] %}
{% set filteredArray = array|filter(v => v[0] == 'b') %}
{# Result: ['bar', 'baz'] #}
```

## `filterByValue`

Runs an array through <api:craft\helpers\ArrayHelper::filterByValue()>.

## `first`

Returns the first element of an array or string.

This works identically to Twig’s core [`first`](https://twig.symfony.com/doc/2.x/filters/first.html) filter.

## `format`

formats a given string by replacing the placeholders (placeholders follows the [sprintf()](https://secure.php.net/sprintf) notation).

This works identically to Twig’s core [`format`](https://twig.symfony.com/doc/2.x/filters/format.html) filter.

## `group`

Groups the items of an array together based on common properties.

```twig
{% set allEntries = craft.entries.section('blog').all() %}
{% set allEntriesByYear = allEntries|group('postDate|date('Y')') %}

{% for year, entriesInYear in allEntriesByYear %}
    <h2>{{ year }}</h2>

    <ul>
        {% for entry in entriesInYear %}
            <li><a href="{{ entry.url }}">{{ entry.title }}</a></li>
        {% endfor %}
    </ul>
{% endfor %}
```

## `hash`

Prefixes the given string with a keyed-hash message authentication code (HMAC), for securely passing data in forms that should not be tampered with.

```twig
<input type="hidden" name="foo" value="{{ 'bar'|hash }}">
```

PHP scripts can validate the value via [Security::validateData()](api:yii\base\Security::validateData()):

```php
$foo = Craft::$app->request->getBodyParam('foo');
$foo = Craft::$app->security->validateData($foo);

if ($foo !== false) {
    // data is valid
}
```

## `id`

Formats a string into something that will work well as an HTML input `id`, via <api:craft\web\View::formatInputId()>.

```twig
{% set name = 'input[name]' %}
<input type="text" name="{{ name }}" id="{{ name|id }}">
```

## `index`

Runs an array through [ArrayHelper::index()](api:yii\helpers\BaseArrayHelper::index()).

```twig
{% set entries = entries|index('id') %}
```

## `indexOf`

Returns the index of a passed-in value within an array, or the position of a passed-in string within another string. (Note that the returned position is 0-indexed.) If no position can be found, `-1` is returned instead.

```twig
{% set colors = ['red', 'green', 'blue'] %}
<p>Green is located at position {{ colors|indexOf('green') + 1 }}.</p>

{% set position = 'team'|indexOf('i') %}
{% if position != -1 %}
    <p>There <em>is</em> an “i” in “team”! It’s at position {{ position + 1 }}.</p>
{% endif %}
```

## `intersect`

Returns an array containing only the values that are also in a passed-in array.

```twig
{% set ownedIngredients = [
    'vodka',
    'gin',
    'triple sec',
    'tonic',
    'grapefruit juice'
] %}

{% set longIslandIcedTeaIngredients = [
    'vodka',
    'tequila',
    'rum',
    'gin',
    'triple sec',
    'sweet and sour mix',
    'Coke'
] %}

{% set ownedLongIslandIcedTeaIngredients =
    ownedIngredients|intersect(longIslandIcedTeaIngredients)
%}
```

## `join`

Returns a string which is the concatenation of the elements in an array.

This works identically to Twig’s core [`join`](https://twig.symfony.com/doc/2.x/filters/join.html) filter.

## `json_encode`

Returns the JSON representation of a value.

This works similarly to Twig’s core [`json_encode`](https://twig.symfony.com/doc/2.x/filters/json_encode.html) filter, except that the `options` argument will default to `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT` if the response content type is either `text/html` or `application/xhtml+xml`.

## `json_decode`

JSON-decodes a string into an array  by passing it through <api:yii\helpers\Json::decode()>.

```twig
{% set arr = '[1, 2, 3]'|json_decode %}
```

## `kebab`

Returns a string formatted in “kebab-case”.

Tip: That’s a reference to [shish kebabs](https://en.wikipedia.org/wiki/Kebab#Shish) for those of you that don’t get the analogy.

```twig
{{ 'foo bar?'|kebab }}
{# Output: foo-bar #}
```

## `keys`

Returns the keys of an array.

This works identically to Twig’s core [`keys`](https://twig.symfony.com/doc/2.x/filters/keys.html) filter.

## `last`

Returns the last element of an array or string.

This works identically to Twig’s core [`last`](https://twig.symfony.com/doc/2.x/filters/last.html) filter.

## `lcfirst`

Lowercases the first character of a string.

## `length`

Returns the number of elements in an array or string.

This works identically to Twig’s core [`length`](https://twig.symfony.com/doc/2.x/filters/length.html) filter.

## `literal`

Runs a string through <api:craft\helpers\Db::escapeParam()>

## `lower`

Converts a value to lowercase.

This works identically to Twig’s core [`lower`](https://twig.symfony.com/doc/2.x/filters/lower.html) filter.

## `map`

Applies an arrow function to the elements of an array.

This works identically to Twig’s core [`map`](https://twig.symfony.com/doc/2.x/filters/map.html) filter.

## `markdown` or `md`

Processes a string with [Markdown](https://daringfireball.net/projects/markdown/).

```twig
{% set content %}
# Everything You Need to Know About Computer Keyboards

The only *real* computer keyboard ever made was famously
the [Apple Extended Keyboard II] [1].

    [1]: https://www.flickr.com/photos/gruber/sets/72157604797968156/
{% endset %}

{{ content|markdown }}
```

This filter supports two arguments:
- `flavor` can be `'original'` (default value), `'gfm'`(GitHub-Flavored Markdown), `'gfm-comment'` (GFM with newlines converted to `<br>`s), or `'extra'` (Markdown Extra)
- `inlineOnly` determines whether to only parse inline elements, omitting any `<p>` tags (defaults to `false`)

## `merge`

Merges an array with another array.

This works identically to Twig’s core [`merge`](https://twig.symfony.com/doc/2.x/filters/merge.html) filter.

## `multisort`

Sorts an array with [ArrayHelper::multisort()](api:yii\helpers\BaseArrayHelper::multisort()).

## `nl2br`

Inserts HTML line breaks before all newlines in a string.

This works identically to Twig’s core [`nl2br`](https://twig.symfony.com/doc/2.x/filters/nl2br.html) filter.

## `number`

Formats a number according to the user’s preferred language.

You can optionally pass `false` to it if you want group symbols to be omitted (e.g. commas in English).

```twig
{{ 1000000|number }}
{# Output: 1,000,000 #}

{{ 1000000|number(false) }}
{# Output: 1000000 #}
```

## `number_format`

Formats numbers. It is a wrapper around PHP’s [number_format()](https://secure.php.net/number_format) function:

This works identically to Twig’s core [`number_format`](https://twig.symfony.com/doc/2.x/filters/number_format.html) filter.

## `parseRefs`

Parses a string for [reference tags](../reference-tags.md).

```twig
{% set content %}
    {entry:blog/hello-world:link} was my first blog post. Pretty geeky, huh?
{% endset %}

{{ content|parseRefs|raw }}
```

## `pascal`

Returns a string formatted in “PascalCase” (AKA “UpperCamelCase”).

```twig
{{ 'foo bar'|pascal }}
{# Output: FooBar #}
```

## `percentage`

Formats a percentage according to the user’s preferred language.

## `raw`

Marks a value as being “safe”, which means that in an environment with automatic escaping enabled this variable will not be escaped if raw is the last filter applied to it.

This works identically to Twig’s core [`raw`](https://twig.symfony.com/doc/2.x/filters/raw.html) filter.

## `reduce`

Iteratively reduces a sequence or a mapping to a single value using an arrow function, so as to reduce it to a single value. The arrow function receives the return value of the previous iteration and the current value of the sequence or mapping.

This works identically to Twig’s core [`reduce`](https://twig.symfony.com/doc/2.x/filters/reduce.html) filter.

## `replace`

Replaces parts of a string with other things.

When a mapping array is passed, this works identically to Twig’s core [`replace`](https://twig.symfony.com/doc/2.x/filters/replace.html) filter:

```twig
{% set str = 'Hello, FIRST LAST' %}

{{ str|replace({
    FIRST: currentUser.firstName,
    LAST:  currentUser.lastName
}) }}
```

Or you can replace one thing at a time:

```twig
{% set str = 'Hello, NAME' %}

{{ str|replace('NAME', currentUser.name) }}
```

You can also use a regular expression to search for matches by starting and ending the replacement string’s value with forward slashes:

```twig
{{ tag.title|lower|replace('/[^\\w]+/', '-') }}
```

## `reverse`

Reverses an array or string.

This works identically to Twig’s core [`reverse`](https://twig.symfony.com/doc/2.x/filters/reverse.html) filter.

## `round`

Rounds a number to a given precision.

This works identically to Twig’s core [`round`](https://twig.symfony.com/doc/2.x/filters/round.html) filter.

## `rss`

Outputs a date in the format required for RSS feeds (`D, d M Y H:i:s O`).

```twig
{{ entry.postDate|rss }}
```

## `slice`

Extracts a slice of an array or string.

This works identically to Twig’s core [`slice`](https://twig.symfony.com/doc/2.x/filters/slice.html) filter.

## `snake`

Returns a string formatted in “snake_case”.

```twig
{{ 'foo bar'|snake }}
{# Output: foo_bar #}
```

## `sort`

Sorts an array.

This works identically to Twig’s core [`sort`](https://twig.symfony.com/doc/2.x/filters/sort.html) filter.

## `spaceless`

Removes whitespace between HTML tags, not whitespace within HTML tags or whitespace in plain text.

This works identically to Twig’s core [`spaceless`](https://twig.symfony.com/doc/2.x/filters/spaceless.html) filter.

## `split`

Splits a string by the given delimiter and returns a list of string.

This works identically to Twig’s core [`split`](https://twig.symfony.com/doc/2.x/filters/split.html) filter.

## `striptags`

Strips SGML/XML tags and replace adjacent whitespace by one space.

This works identically to Twig’s core [`striptags`](https://twig.symfony.com/doc/2.x/filters/striptags.html) filter.

## `time`

Outputs the time of day for a timestamp or [DateTime](http://php.net/manual/en/class.datetime.php) object.

```twig
{{ entry.postDate|time }}
{# Output: 10:00:00 AM #}
```

Craft provides some special format keywords that will output locale-specific time formats:

```twig
{{ entry.postDate|time('short') }}
{# Output: 10:00 AM #}
```

Possible `format` values are:

| Format               | Example        |
| -------------------- | -------------- |
| `short`              | 5:00 PM        |
| `medium` _(default)_ | 5:00:00 PM     |
| `long`               | 5:00:00 PM PDT |

The current application locale will be used by default. If you want to format the date and time for a different locale, use the `locale` argument:

```twig
{{ entry.postDate|time('short', locale='en-GB') }}
{# Output: 17:00 #}
```

You can customize the timezone the time is output in, using the `timezone` param:

```twig
{{ entry.postDate|time('short', timezone='UTC') }}
{# Output: 12:00 AM #}
```

## `timestamp`

Formats a date as a human-readable timestamp, via <api:craft\i18n\Formatter::asTimestamp()>.

## `title`

Returns a titlecased version of the value. Words will start with uppercase letters, all remaining characters are lowercase.

This works identically to Twig’s core [`title`](https://twig.symfony.com/doc/2.x/filters/title.html) filter.

## `trim`

Strips whitespace (or other characters) from the beginning and end of a string

This works identically to Twig’s core [`trim`](https://twig.symfony.com/doc/2.x/filters/trim.html) filter.

## `translate` or `t`

Translates a message with [Craft::t()](api:yii\BaseYii::t()).

```twig
{{ 'Hello world'|t('myCategory') }}
```

If no category is specified, it will default to `site`.

```twig
{{ 'Hello world'|t }}
```

::: tip
See [Static Message Translations](../static-translations.md) for a full explanation on how this works.
:::

## `ucfirst`

Capitalizes the first character of a string.

## `ucwords`

Capitalizes the first character of each word in a string.

## `unique`

Runs an array through [array_unique()](http://php.net/manual/en/function.array-unique.php).

## `upper`

Converts a value to uppercase.

This works identically to Twig’s core [`upper`](https://twig.symfony.com/doc/2.x/filters/upper.html) filter.

## `url_encode`

Percent-encodes a given string as URL segment or an array as query string.

This works identically to Twig’s core [`url_encode`](https://twig.symfony.com/doc/2.x/filters/url_encode.html) filter.

## `values`

Returns an array of all the values in a given array, but without any custom keys.

```twig
{% set arr1 = {foo: 'Foo', bar: 'Bar'} %}
{% set arr2 = arr1|values %}
{# arr2 = ['Foo', 'Bar'] #}
```

## `without`

Returns an array without the specified element(s).

```twig
{% set entries = craft.entries.section('articles').limit(3).find %}
{% set firstEntry = entries[0] %}
{% set remainingEntries = entries|without(firstEntry) %}
```

## `withoutKey`

Returns an array without the specified key.

```twig
{% set array = {
    foo: 'foo',
    bar: 'bar',
    baz: 'baz'
} %}
{% set filtered = array|withoutKey('baz') %}
```
