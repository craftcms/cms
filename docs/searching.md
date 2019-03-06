# Searching

You can search for elements anywhere you see this bar:

![Search Bar](./images/searching-search-bar.png)

## Supported syntaxes

Craft supports the following search syntax:

Searching for… | will find elements…
-|-
`salty` | containing “salty”.
`salty dog` | containing both “salty” and “dog”.
`salty OR dog` | containing either “salty” or “dog” (or both).
`salty -dog` | containing “salty” but not “dog”.
`"salty dog"` | containing the exact phrase “salty dog”.
`*ty` | containing a word that ends with “ty”.
`*alt*` | containing a word that contains “alt”.
`body:salty` | where the `body` field contains “salty”.
`body:salty body:dog` | where the `body` field contains both “salty” and “dog”.
`body:salty OR body:dog` | where the `body` field contains either “salty” or “dog”.
`body:salty -body:dog` | where the `body` field contains “salty” but not “dog”.
`body:"salty dog"` | where the `body` field contains the exact phrase “salty dog”.
`body:*ty` | where the `body` field contains a word that ends with “ty”.
`body:*alt*` | where the `body` field contains a word that contains “alt”.
`body::salty` | where the `body` field is set to “salty” and nothing more.
`body::"salty dog"` | where the `body` field is set to “salty dog” and nothing more.
`body::salty*` | where the `body` field begins with “salty”.
`body::*dog` | where the `body` field ends with “dog”.
`body:*` | where the `body` field contains any value.
`-body:*` | where the `body` field is empty.

## Searching for specific element attributes

Assets, categories, entries, users, and tags each support their own set of additional attributes to search against:

* **Assets**

  * filename
  * extension
  * kind

* **Categories**

  * title
  * slug

* **Entries**

  * title
  * slug

* **Users**

  * username
  * firstName
  * lastName
  * fullName (firstName + lastName)
  * email

* **Tags**

  * title


## Templating

`craft.assets()`, `craft.entries()`, `craft.tags()`, and `craft.users()` support a `search` parameter that you can use to filter their elements by a given search query.

```twig
{# Get the user's search query from the 'q' query-string param #}
{% set searchQuery = craft.app.request.getParam('q') %}

{# Fetch entries that match the search query #}
{% set results = craft.entries()
    .search(searchQuery)
    .all() %}
```

### Ordering results by score

You can also set the `orderBy` parameter to `'score'` if you want results ordered by best-match to worst-match:

```twig
{% set results = craft.entries()
    .search(searchQuery)
    .orderBy('score')
    .all() %}
```

When you do this, each of the elements returned will have a `searchScore` attribute set, which reveals what their search score was.

> See our [Search Form](dev/examples/search-form.md) tutorial for a complete example of listing dynamic search results.

## Rebuilding your Search Indexes

Craft does its best to keep its search indexes as up-to-date as possible, but there are a couple things that might render portions of them inaccurate. If you suspect that your search indexes don’t have the latest and greatest data, you can have Craft rebuild them with the Rebuild Search Indexes tool in Settings.
