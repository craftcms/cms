# `craft.feeds`

You can capture RSS and Atom feeds using `craft.feeds`.

## Methods

The following methods are available:

### `getFeedItems( url, limit, offset, cacheDuration )`

Returns the items in the feed located at the given URL.

Only the first argument is required (`url`). If `limit` is passed, the function will return no more than that many items. If `offset` is passed, that many items will be skipped from the beginning of the feed. And if `cacheDuration` is passed, the results will be cached for the given amount of time. (If no `cacheDuration` is passed, Craft will default to the time specified by the [cacheDuration](../config-settings.md#cacheDuration) config setting.)

#### Item Properties

Each item will have the following properties:

* **`authors`** – An array of the item’s authors. Each element is a sub-array with the values `name`, `url`, and `email`.
* **`categories`** – An array of the item’s categories. Each element is a sub-array with the values `term`, `scheme`, and `label`.
* **`content`** – The item’s main content.
* **`contributors`** – An array of author info. Each element is a sub-array with the values `name`, `url`, and `email`.
* **`date`** – The item’s date.
* **`dateUpdated`** – The item’s last updated date.
* **`permalink`** – The item’s permalink URL.
* **`summary`** – The item’s summary content.
* **`title`** – The item’s title.

Here’s a basic example of what it all might look like:

```twig
{% set feedUrl = "http://feeds.feedburner.com/blogandtonic" %}
{% set limit = 10 %}
{% set items = craft.feeds.getFeedItems(feedUrl, limit) %}

{% for item in items %}
    <article>
        <h3><a href="{{ item.permalink }}">{{ item.title }}</a></h3>
        <p class="author">{{ item.authors[0].name }}</p>
        <p class="date">{{ item.date }}</p>

        {{ item.summary }}
    </article>
{% endfor %}
```
