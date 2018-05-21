# Searching

You can search for elements anywhere you see this bar:

![Search Bar](./images/searching-search-bar.png)

## Supported syntaxes

Craft supports the following search syntax:

<table>
    <thead>
        <tr>
            <th>Searching for…</th>
            <th>will find elements…</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>salty</td>
            <td>containing the word “salty”.</td>
        </tr>
        <tr>
            <td>body:salty</td>
            <td>where the ‘body’ field contains “salty”.</td>
        </tr>
        <tr>
            <td>salty dog</td>
            <td>containing both “salty” and “dog”.</td>
        </tr>
        <tr>
            <td>body:salty body:dog</td>
            <td>where the ‘body’ field contains both “salty” and “dog”.</td>
        </tr>
        <tr>
            <td>salty OR dog</td>
            <td>containing either “salty” or “dog” (or both).</td>
        </tr>
        <tr>
            <td>body:salty OR body:dog</td>
            <td>where the ‘body’ field contains either “salty” or “dog”.</td>
        </tr>
        <tr>
            <td>salty -dog</td>
            <td>containing “salty” but not “dog”.</td>
        </tr>
        <tr>
            <td>body:salty -body:dog</td>
            <td>where the ‘body’ field contains “salty” but not “dog”.</td>
        </tr>
        <tr>
            <td>"salty dog"</td>
            <td>containing the exact phrase “salty dog”.</td>
        </tr>
        <tr>
            <td>body:"salty dog"</td>
            <td>where the ‘body’ field contains the exact phrase “salty dog”.</td>
        </tr>
        <tr>
            <td>salty locale:en_us</td>
            <td>containing “salty” in the ‘en_us’ locale.</td>
        </tr>
        <tr>
            <td>sal*</td>
            <td>containing a word that begins with “sal”.</td>
        </tr>
        <tr>
            <td>*ty</td>
            <td>containing a word that ends with “ty”.</td>
        </tr>
        <tr>
            <td>*alt*</td>
            <td>containing a word that contains “alt”.</td>
        </tr>
        <tr>
            <td>body:sal*</td>
            <td>where the ‘body’ field contains a word that begins with “sal”.</td>
        </tr>
        <tr>
            <td>body::salty*</td>
            <td>where the ‘body’ field begins with “salty”.</td>
        </tr>
        <tr>
            <td>body::"salty dog"</td>
            <td>where the ‘body’ field is set to “salty dog” and nothing more.</td>
        </tr>
        <tr>
            <td>body:*</td>
            <td>where the ‘body’ field contains <em>any</em> value.</td>
        </tr>
    </tbody>
</table>

> You can alter the default behavior of search terms with the <config:defaultSearchTermOptions> config setting. See [Enabling Fuzzy Search by Default](https://craftcms.com/support/enabling-fuzzy-search-by-default) for more info.

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

You can specify the search query in two different ways:

```twig
{% set query = craft.request.getParam('q') %}

{# Pass the search query directly into the search param: #}
{% set results = craft.entries({
    search: query
}).all() %}

{# Or pass it along with some custom search term options: #}
{% set results = craft.entries({
    search: {
        query: query,
        subLeft: true,
        subRight: true                
    }
}).all() %}
```

If you go the latter route, note that the `query` property is required. Beyond that, you can use all of the same keys available to the <config:defaultSearchTermOptions> config setting.

### Ordering results by score

You can also set the `orderBy` parameter to `'score'` if you want results ordered by best-match to worst-match:

```twig
{% set results = craft.entries({
    search: query,
    orderBy: 'score'
}).all() %}
```

When you do this, each of the elements returned will have a `searchScore` attribute set, which reveals what their search score was.

> See our [Search Form](templating/examples/search-form.md) tutorial for a complete example of listing dynamic search results.

## Rebuilding your Search Indexes

Craft does its best to keep its search indexes as up-to-date as possible, but there are a couple things that might render portions of them inaccurate. If you suspect that your search indexes don’t have the latest and greatest data, you can have Craft rebuild them with the Rebuild Search Indexes tool in Settings.
