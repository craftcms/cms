# Searching

You can search for elements anywhere you see this bar:

![search](https://craftcmsassets.craftcdn.com/images/docs/search.png)

## Supported syntaxes

Craft supports the following search syntax:

Searching for… | will find elements…
-|-
salty | containing the word “salty”.
body:salty | where the ‘body’ field contains “salty”.
salty dog | containing both “salty” and “dog”.
body:salty body:dog | where the ‘body’ field contains both “salty” and “dog”.
salty OR dog | containing either “salty” or “dog” (or both).
body:salty OR body:dog | where the ‘body’ field contains either “salty” or “dog”.
salty -dog | containing “salty” but not “dog”.
body:salty -body:dog | where the ‘body’ field contains “salty” but not “dog”.
\"salty dog\" | containing the exact phrase “salty dog”.
body:\"salty dog\" | where the ‘body’ field contains the exact phrase “salty dog”.
salty locale:en_us | containing “salty” in the ‘en_us’ locale.
sal* | containing a word that begins with “sal”.
*ty | containing a word that ends with “ty”.
\*alt\* | containing a word that contains “alt”.
body:sal* | where the ‘body’ field contains a word that begins with “sal”.
body::salty* | where the ‘body’ field begins with “salty”.
body::\"salty dog\" | where the ‘body’ field is set to “salty dog” and nothing more.
body:* | where the ‘body’ field contains any value.
-body:* | where the ‘body’ field is empty.

::: tip
You can alter the default behavior of search terms with the [defaultSearchTermOptions](config-settings.md#defaultSearchTermOptions) config setting. See _[Enabling Fuzzy Search by Default](https://craftcms.com/support/enabling-fuzzy-search-by-default)_ for more info.
:::

## Searching for specific element attributes

Assets, categories, entries, users, and tags each support their own set of additional attributes to search against:

* **Assets**

  - filename
  - extension
  - kind

* **Categories**

  - title
  - slug

* **Entries**

  - title
  - slug

* **Users**

  - username
  - firstName
  - lastName
  - fullName (firstName + lastName)
  - email

* **Tags**

  - title


## Templating

[craft.assets](templating/craft.assets.md), [craft.entries](templating/craft.entries.md), [craft.tags](templating/craft.tags.md), and [craft.users](templating/craft.users.md) support a `search` parameter which lets you filter their elements by a given search query.

You can specify the search query in two different ways:

```twig
{% set query = craft.request.getParam('q') %}

{# Pass the search query directly into the search param: #}
{% set results = craft.entries({
    search: query
}) %}

{# Or pass it along with some custom search term options: #}
{% set results = craft.entries({
    search: {
        query: query,
        subLeft: true,
        subRight: true
    }
}) %}
```

If you go the latter route, note that the `query` property is required. Beyond that, all of the same keys available to the [defaultSearchTermOptions](config-settings.md#defaultSearchTermOptions) config setting can also be used here.

### Ordering results by score

You can also set the ‘order’ parameter to `'score'` if you want results ordered by best-match to worst-match:

```twig
{% set results = craft.entries({
    search: query,
    order: 'score'
}) %}
```

When you do this, each of the elements returned will have a `searchScore` attribute set, which reveals what their search score was.

::: tip
See our [Search Form](templating/search-form.md) tutorial for a complete example of listing dynamic search results.
:::

## Rebuilding your Search Indexes

Craft does its best to keep its search indexes as up-to-date as possible, but there are a couple things that might render portions of them inaccurate. If you suspect that your search indexes don’t have the latest and greatest data, you can have Craft completely rebuild them with the Rebuild Search Indexes tool in Settings.
