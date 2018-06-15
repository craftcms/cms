# Filters

In addition to the template filters that [Twig comes with](http://twig.sensiolabs.org/doc/filters/index.html), Craft provides a few of its own.

## `atom`

Outputs a date in the ISO-8601 format (which should be used for Atom feeds, among other things).

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

## `currency( currency, numberOptions, textOptions, stripZeroCents )`

Formats a number with a given currency according to the user’s preferred language.

If you pass `true` into the last argument, the “.00” will be stripped if there’s zero cents.

See [here for a list](https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter#$numberFormatterOptions-detail) of the possible `numberOptions`.

See [here for a list](https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter#$numberFormatterTextOptions-detail) of the possible textOptions`.

```twig
{{ 1000000|currency('USD') }} => $1,000,000.00
{{ 1000000|currency('USD', [], [], true) }} => $1,000,000
```

## `date`

Like Twig’s core [date](https://twig.symfony.com/doc/2.x/filters/date.html) filter, but with additional support for the following `format` values:

- `'short'`
- `'medium'` (default)
- `'long'`
- `'full'`

When one of those formats are used, the date will be formatted into a localized date format using <craft\i18n\Formatter::asDate()>.

A `translate` argument is also available. If `true` is passed, the formatted date will be run through <api:craft\helpers\DateTimeHelper::translateDate()> before being returned.

```twig
{{ entry.postDate|date('short') }}
```
 

## `datetime`

Like the [date](#date) filter, but the result will also include a timestamp.

```twig
{{ entry.postDate|datetime('short') }}
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
$foo = craft()->request->getPost('foo');
$foo = craft()->security->validateData($foo);

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

## `kebab`

Returns a string formatted in “kebab-case”. 

Tip: That’s a reference to [shish kebabs](http://en.wikipedia.org/wiki/Kebab#Shish) for those of you that don’t get the analogy.

```twig
{{ "foo bar?"|kebab }}
{# Outputs: foo-bar #}
```

## `lcfirst`

Lowercases the first character of a string.

## `literal`

Runs a string through <api:craft\helpers\Db::escapeParam>

## `markdown` or `md`

Processes a string with [Markdown](http://daringfireball.net/projects/markdown/).

```twig
{% set content %}
# Everything You Need to Know About Computer Keyboards

The only *real* computer keyboard ever made was famously
the [Apple Extended Keyboard II] [1].
    
    [1]: http://www.flickr.com/photos/gruber/sets/72157604797968156/
{% endset %}

{{ content|markdown }}
```

## `multisort`

Sorts an array with [ArrayHelper::multisort()](api:yii\helpers\BaseArrayHelper::multisort()).

## `number`

Formats a number according to the user’s preferred language.

You can optionally pass `false` to it if you want group symbols to be omitted (e.g. commas in English).

```twig
{{ 1000000|number }} => 1,000,000
{{ 1000000|number(false) }} => 1000000
```

## `parseRefs`

Parses a string for [reference tags](reference-tags.md).

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
{{ tag.name|lower|replace('/[^\\w]+/', '-') }}
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

Like the [time](#time) filter, but for times rather than dates.

```twig
{{ entry.postDate|time('short') }}
```

## `timestamp`

Formats a date as a human-readable timestamp, via <craft\i18n\Formatter::asTimestamp()>.

## `translate` or `t`

Translates a message with [Craft::t()](api:yii\BaseYii::t()). If no category is specified, it will default to `site`.

```twig
{{ "Hello world"|t }}
``` 

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
