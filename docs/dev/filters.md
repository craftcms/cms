# Filters

In addition to the template filters that [Twig comes with](https://twig.symfony.com/doc/filters/index.html), Craft provides a few of its own.

## `atom`

Converts a date to an ISO-8601 timestamp (e.g. `2019-01-29T10:00:00-08:00`), which should be used for Atom feeds, among other things.

```twig
{{ entry.postDate|atom }}
```

## `camel`

Returns a string formatted in “camelCase”.

```twig
{{ "foo bar"|camel }}
{# Outputs: fooBar #}
```

## `column`

Runs an array through [ArrayHelper::getColumn()](api:yii\helpers\BaseArrayHelper::getColumn()) and returns the result.

```twig
{% set entryIds = entries|column('id') %}
```

## `currency( currency, numberOptions, textOptions, stripZeros )`

Formats a number with a given currency according to the user’s preferred language.

If you pass `true` into the last argument, the fraction digits will be removed if the value to be formatted has no minor value (e.g. cents).

See [here for a list](api:yii\i18n\Formatter::$numberFormatterOptions) of the possible `numberOptions`.

See [here for a list](api:yii\i18n\Formatter::$numberFormatterTextOptions) of the possible `textOptions`.

```twig
{{ 1000000|currency('USD') }} → $1,000,000.00
{{ 1000000|currency('USD', [], [], true) }} → $1,000,000
```

## `date`

Outputs a formatted date for a timestamp or [DateTime](http://php.net/manual/en/class.datetime.php) object.

```twig
{{ entry.postDate|date }} → Sep 26, 2018
```

You can customize how much detail is provided by passing a value to the `format` param:

```twig
{{ entry.postDate|date('short') }} → 9/26/2018
```

Possible `format` values are:

| Format               | Example                       |
| -------------------- | ----------------------------- |
| `short`              | 9/26/2018                     |
| `medium` _(default)_ | Sep 26, 2018                  |
| `long`               | September 26, 2018            |
| `full`               | Wednesday, September 26, 2018 |

The exact time formats that will be used depends on the current application locale. If you want to use a different locale’s time format, use the `locale` param:

```twig
{{ entry.postDate|date('short', locale='en-GB') }} → 26/9/2018
```

You can also pass a custom PHP date format using the same [formatting options](http://php.net/manual/en/function.date.php) supported by PHP’s `date()` function.

```twig
{{ entry.postDate|date('Y-m-d') }} → 2018-09-26
```

You can customize the timezone the time is output in, using the `timezone` param:

```twig
{{ entry.postDate|date('short', timezone='UTC') }} → 9/27/2018
```

## `datetime`

Outputs a formatted date (including time of day) for a timestamp or [DateTime](http://php.net/manual/en/class.datetime.php) object.

```twig
{{ entry.postDate|datetime }} → Sep 26, 2018, 5:00:00 PM
```

You can customize how much detail is provided by passing a value to the `format` param:

```twig
{{ entry.postDate|datetime('short') }} → 9/26/2018, 5:00 PM
```

Possible `format` values are:

| Format               | Example                                         |
| -------------------- | ----------------------------------------------- |
| `short`              | 9/26/2018, 5:00 PM                              |
| `medium` _(default)_ | Sep 26, 2018, 5:00:00 PM                        |
| `long`               | September 26, 2018 at 5:00:00 PM PDT            |
| `full`               | Wednesday, September 26, 2018 at 5:00:00 PM PDT |

The exact time formats that will be used depends on the current application locale. If you want to use a different locale’s time format, use the `locale` param:

```twig
{{ entry.postDate|datetime('short', locale='en-GB') }} → 26/9/2018, 17:00
```

You can customize the timezone the time is output in, using the `timezone` param:

```twig
{{ entry.postDate|datetime('short', timezone='UTC') }} → 9/27/2018, 12:00 AM
```

## `duration`

Runs a [DateInterval](http://php.net/manual/en/class.dateinterval.php) object through <api:craft\helpers\DateTimeHelper::humanDurationFromInterval()>

```twig
<p>Posted {{ entry.postDate.diff(now)|duration(false) }} ago.</p>
```

## `encenc`

Encrypts and base64-encodes a string.

```twig
{{ "secure-string"|encenc }}
```

## `filesize`

Formats a number of bytes into something nicer.

## `filter`

Removes any empty elements from an array and returns the modified array.

## `filterByValue`

Runs an array through <api:craft\helpers\ArrayHelper::filterByValue()>.

## `group`

Groups the items of an array together based on common properties.

```twig
{% set allEntries = craft.entries.section('blog').all() %}
{% set allEntriesByYear = allEntries|group('postDate|date("Y")') %}

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
$foo = Craft::$app->request->getPost('foo');
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

{% set position = "team"|indexOf('i') %}
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

## `json_encode`

Like Twig’s core [json_encode](https://twig.symfony.com/doc/2.x/filters/json_encode.html) filter, but if the `options` argument isn’t set, it will default to `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT` if the response content type is either `text/html` or `application/xhtml+xml`.

## `json_decode`

JSON-decodes a string into an array  by passing it through <api:yii\helpers\Json::decode()>.

```twig
{% set arr = '[1, 2, 3]'|json_decode %}
```

## `kebab`

Returns a string formatted in “kebab-case”.

Tip: That’s a reference to [shish kebabs](https://en.wikipedia.org/wiki/Kebab#Shish) for those of you that don’t get the analogy.

```twig
{{ "foo bar?"|kebab }}
{# Outputs: foo-bar #}
```

## `lcfirst`

Lowercases the first character of a string.

## `literal`

Runs a string through <api:craft\helpers\Db::escapeParam()>

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

## `multisort`

Sorts an array with [ArrayHelper::multisort()](api:yii\helpers\BaseArrayHelper::multisort()).

## `number`

Formats a number according to the user’s preferred language.

You can optionally pass `false` to it if you want group symbols to be omitted (e.g. commas in English).

```twig
{{ 1000000|number }} → 1,000,000
{{ 1000000|number(false) }} → 1000000
```

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
{{ "foo bar"|pascal }}
{# Outputs: FooBar #}
```

## `percentage`

Formats a percentage according to the user’s preferred language.

## `replace`

Replaces parts of a string with other things.

You can replace multiple things at once by passing in an object of search/replace pairs:

```twig
{% set str = "Hello, FIRST LAST" %}

{{ str|replace({
    FIRST: currentUser.firstName,
    LAST:  currentUser.lastName
}) }}
```

Or you can replace one thing at a time:

```twig
{% set str = "Hello, NAME" %}

{{ str|replace('NAME', currentUser.name) }}
```

You can also use a regular expression to search for matches by starting and ending the replacement string’s value with forward slashes:

```twig
{{ tag.title|lower|replace('/[^\\w]+/', '-') }}
```

## `round`

Rounds off a number to the closest integer.

```twig
{{ 42.1|round }} → 42
{{ 42.9|round }} → 43
```

## `rss`

Outputs a date in the format required for RSS feeds (`D, d M Y H:i:s O`).

```twig
{{ entry.postDate|rss }}
```

## `snake`

Returns a string formatted in “snake_case”.

```twig
{{ "foo bar"|snake }}
{# Outputs: foo_bar #}
```

## `time`

Outputs the time of day for a timestamp or [DateTime](http://php.net/manual/en/class.datetime.php) object.

```twig
{{ entry.postDate|time }} → 10:00:00 AM
```

You can customize how much detail is provided by passing a value to the `format` param:

```twig
{{ entry.postDate|time('short') }} → 10:00 AM
```

Possible `format` values are:

| Format               | Example        |
| -------------------- | -------------- |
| `short`              | 5:00 PM        |
| `medium` _(default)_ | 5:00:00 PM     |
| `long`               | 5:00:00 PM PDT |

The exact time formats that will be used depends on the current application locale. If you want to use a different locale’s time format, use the `locale` param:

```twig
{{ entry.postDate|time('short', locale='en-GB') }} → 17:00
```

You can customize the timezone the time is output in, using the `timezone` param:

```twig
{{ entry.postDate|time('short', timezone='UTC') }} → 12:00 AM
```

## `timestamp`

Formats a date as a human-readable timestamp, via <api:craft\i18n\Formatter::asTimestamp()>.

## `translate` or `t`

Translates a message with [Craft::t()](api:yii\BaseYii::t()).

```twig
{{ "Hello world"|t('myCategory') }}
```

If no category is specified, it will default to `site`.

```twig
{{ "Hello world"|t }}
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

## `values`

Returns an array of all the values in a given array, but without any custom keys.

```twig
{% set arr1 = {foo: "Foo", bar: "Bar"} %}
{% set arr2 = arr1|values %}
{# arr2 = ["Foo", "Bar"] #}
```

## `without`

Returns an array without the specified element(s).

```twig
{% set entries = craft.entries.section('articles').limit(3).find %}
{% set firstEntry = entries[0] %}
{% set remainingEntries = entries|without(firstEntry) %}
```
