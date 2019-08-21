# Introduction To GraphQL

GraphQL is a query language for APIs and a runtime for fulfilling those queries with your existing data. GraphQL provides a complete and understandable description of the data in your API, gives clients the power to ask for exactly what they need and nothing more, makes it easier to evolve APIs over time, and enables powerful developer tools.

Learn more about GraphQL [here](https://graphql.org/).

# Getting Started

Before you start using GraphQL ony your Craft installation, there are a few things to keep in mind.

## Setting Up

To set up your GraphQL endpoint, you should set up a [dedicated route for it](https://docs.craftcms.com/v3/routing.html#advanced-routing-with-url-rules) and point it to the `Gql` controller. For example, you could set it up like this:

```php
return [
    'api' => 'gql',
    // ...
];
```

### GraphQL Tokens.

Before performing any queries, you'll need to set up a token with the permissions for querying Craft. To do that, go to Settings → GraphQL Tokens → New token.

The access token itself will be generated upon creating the token, but you have the option to name the token, set its expiry date (if any), setting its status and selecting what permissions does the token have.

Due to the nature of GraphQL, the permissions are enforced in a more strict manner as opposed to Craft. For example, if a user is not permitted to view a volume `Portrait Photos`, but is permitted to view an entry that photos related from that Volume, the user will be able to see those photos. If fetching the entry using a GraphQL query with a token that has similar permissions, the returned data will automatically filter out the photos which are not permitted by the token permissions, while leaving all the other assets in place.

Furthermore, if the token used doesn't have the permission to view any volume at all, the schema returned for that token will not include the possibility to query for assets at all. This allows for very granular content API permissions to be created.

### Making the request

Once you have a token created, you can try a simple ping request with `{ping}` as the query. Craft GraphQL uses the "Bearer" authentication scheme. If this is starting to sound complicated, you're probably better off just using one of the many available tools for taking the GraphQL API out for a spin:

* [Insomnia](https://insomnia.rest/)
* [GraphQL Playground](https://github.com/prisma/graphql-playground)
* [GraphiQL](https://github.com/graphql/graphiql/tree/master/packages/graphiql#readme)
* [GraphQL Playground online](https://www.graphqlbin.com/v2/new)

If you prefer just using cURL, then  you should be specifying the Bearer token using the `Authorization` header. The best way to pass the payload to GraphQL API is using a `query` POST parameter.

### Dev Mode

When using the GraphQL API, the GraphQL Schema is lazy-loaded by default. This means that there might be some type information missing from the schema, unless you query for it. While perfectly fine for live sites, since it saves on performance, this is sub-optimal for sites in development where you are likely to use external tools to browse If [Dev Mode](https://craftcms.com/guides/what-dev-mode-does) is enabled, the entire GraphQL schema is generated, resulting in a small performance loss and a complete GraphQL schema. Also, when Dev Mode is enabled, caching is disabled.

# Fetching data

The GraphQL schema evolves with changes to your site's structure and content model. This section of the documentation serves as an insight into what GraphQL queries, directives and types are available, however, it is important to remember that this is just a starting point. The real availability of GraphQL data is determined by your site, content model and plugins, so be sure to refer to the generated GraphQL schema for the most accurate information.

## Queries

The availability of the queries is subject to the permissions of the token used. The built in queries try to follow their Craft counterparts very closely and they try to support the same arguments with a few exceptions.

### The `queryAssets` query
This query is used to query for assets.

#### The `id` argument
Narrows the query results based on the {elements}’ IDs.

#### The `uid` argument
Narrows the query results based on the {elements}’ UIDs.

#### The `status` argument
Narrows the query results based on the elements’ statuses.

#### The `archived` argument
Narrows the query results to only elements that have been archived.

#### The `trashed` argument
Narrows the query results to only elements that have been soft-deleted.

#### The `site` argument
Determines which site(s) the elements should be queried in. Defaults to the primary site.

#### The `siteId` argument
Determines which site(s) the elements should be queried in. Defaults to the primary site.

#### The `unique` argument
Determines whether only elements with unique IDs should be returned by the query.

#### The `enabledForSite` argument
Narrows the query results based on whether the elements are enabled in the site they’re being queried in, per the `site` argument.

#### The `title` argument
Narrows the query results based on the elements’ titles.

#### The `slug` argument
Narrows the query results based on the elements’ slugs.

#### The `uri` argument
Narrows the query results based on the elements’ URIs.

#### The `search` argument
Narrows the query results to only elements that match a search query.

#### The `ref` argument
Narrows the query results based on a reference string.

#### The `fixedOrder` argument
Causes the query results to be returned in the order specified by the `id` argument.

#### The `inReverse` argument
Causes the query results to be returned in reverse order.

#### The `dateCreated` argument
Narrows the query results based on the {elements}’ creation dates.

#### The `dateUpdated` argument
Narrows the query results based on the {elements}’ last-updated dates.

#### The `offset` argument
Sets the offset for paginated results.

#### The `limit` argument
Sets the limit for paginated results.

#### The `orderBy` argument
Sets the field the returned elements should be ordered by

#### The `volumeId` argument
Narrows the query results based on the volumes the assets belong to, per the volumes’ IDs.

#### The `volume` argument
Narrows the query results based on the volumes the assets belong to, per the volumes’ handles.

#### The `folderId` argument
Narrows the query results based on the folders the assets belong to, per the folders’ IDs.

#### The `filename` argument
Narrows the query results based on the assets’ filenames.

#### The `kind` argument
Narrows the query results based on the assets’ file kinds.

#### The `height` argument
Narrows the query results based on the assets’ image heights.

#### The `width` argument
Narrows the query results based on the assets’ image widths.

#### The `size` argument
Narrows the query results based on the assets’ file sizes (in bytes).

#### The `dateModified` argument
Narrows the query results based on the assets’ files’ last-modified dates.

#### The `includeSubfolders` argument
Broadens the query results to include assets from any of the subfolders of the folder specified by `folderId`.

### The `queryEntries` query
This query is used to query for entries.

#### The `id` argument
Narrows the query results based on the {elements}’ IDs.

#### The `uid` argument
Narrows the query results based on the {elements}’ UIDs.

#### The `status` argument
Narrows the query results based on the elements’ statuses.

#### The `archived` argument
Narrows the query results to only elements that have been archived.

#### The `trashed` argument
Narrows the query results to only elements that have been soft-deleted.

#### The `site` argument
Determines which site(s) the elements should be queried in. Defaults to the primary site.

#### The `siteId` argument
Determines which site(s) the elements should be queried in. Defaults to the primary site.

#### The `unique` argument
Determines whether only elements with unique IDs should be returned by the query.

#### The `enabledForSite` argument
Narrows the query results based on whether the elements are enabled in the site they’re being queried in, per the `site` argument.

#### The `title` argument
Narrows the query results based on the elements’ titles.

#### The `slug` argument
Narrows the query results based on the elements’ slugs.

#### The `uri` argument
Narrows the query results based on the elements’ URIs.

#### The `search` argument
Narrows the query results to only elements that match a search query.

#### The `ref` argument
Narrows the query results based on a reference string.

#### The `fixedOrder` argument
Causes the query results to be returned in the order specified by the `id` argument.

#### The `inReverse` argument
Causes the query results to be returned in reverse order.

#### The `dateCreated` argument
Narrows the query results based on the {elements}’ creation dates.

#### The `dateUpdated` argument
Narrows the query results based on the {elements}’ last-updated dates.

#### The `offset` argument
Sets the offset for paginated results.

#### The `limit` argument
Sets the limit for paginated results.

#### The `orderBy` argument
Sets the field the returned elements should be ordered by

#### The `withStructure` argument
Explicitly determines whether the query should join in the structure data.

#### The `structureId` argument
Determines which structure data should be joined into the query.

#### The `level` argument
Narrows the query results based on the elements’ level within the structure.

#### The `hasDescendants` argument
Narrows the query results based on whether the elements have any descendants.

#### The `ancestorOf` argument
Narrows the query results to only elements that are ancestors of another element.

#### The `ancestorDist` argument
Narrows the query results to only elements that are up to a certain distance away from the element specified by `ancestorOf`.

#### The `descendantOf` argument
Narrows the query results to only elements that are descendants of another element.

#### The `descendantDist` argument
Narrows the query results to only elements that are up to a certain distance away from the element specified by `descendantOf`.

#### The `leaves` argument
Narrows the query results based on whether the elements are “leaves” (element with no descendants).

#### The `editable` argument
Whether to only return entries that the user has permission to edit.

#### The `section` argument
Narrows the query results based on the section handles the entries belong to.

#### The `sectionId` argument
Narrows the query results based on the sections the entries belong to, per the sections’ IDs.

#### The `type` argument
Narrows the query results based on the entries’ entry type handles.

#### The `typeId` argument
Narrows the query results based on the entries’ entry types, per the types’ IDs.

#### The `authorId` argument
Narrows the query results based on the entries’ authors.

#### The `authorGroup` argument
Narrows the query results based on the user group the entries’ authors belong to.

#### The `postDate` argument
Narrows the query results based on the entries’ post dates.

#### The `before` argument
Narrows the query results to only entries that were posted before a certain date.

#### The `after` argument
Narrows the query results to only entries that were posted on or after a certain date.

#### The `expiryDate` argument
Narrows the query results based on the entries’ expiry dates.

### The `queryGlobalSets` query
This query is used to query for global sets.

#### The `id` argument
Narrows the query results based on the {elements}’ IDs.

#### The `uid` argument
Narrows the query results based on the {elements}’ UIDs.

#### The `status` argument
Narrows the query results based on the elements’ statuses.

#### The `archived` argument
Narrows the query results to only elements that have been archived.

#### The `trashed` argument
Narrows the query results to only elements that have been soft-deleted.

#### The `site` argument
Determines which site(s) the elements should be queried in. Defaults to the primary site.

#### The `siteId` argument
Determines which site(s) the elements should be queried in. Defaults to the primary site.

#### The `unique` argument
Determines whether only elements with unique IDs should be returned by the query.

#### The `enabledForSite` argument
Narrows the query results based on whether the elements are enabled in the site they’re being queried in, per the `site` argument.

#### The `title` argument
Narrows the query results based on the elements’ titles.

#### The `slug` argument
Narrows the query results based on the elements’ slugs.

#### The `uri` argument
Narrows the query results based on the elements’ URIs.

#### The `search` argument
Narrows the query results to only elements that match a search query.

#### The `ref` argument
Narrows the query results based on a reference string.

#### The `fixedOrder` argument
Causes the query results to be returned in the order specified by the `id` argument.

#### The `inReverse` argument
Causes the query results to be returned in reverse order.

#### The `dateCreated` argument
Narrows the query results based on the {elements}’ creation dates.

#### The `dateUpdated` argument
Narrows the query results based on the {elements}’ last-updated dates.

#### The `offset` argument
Sets the offset for paginated results.

#### The `limit` argument
Sets the limit for paginated results.

#### The `orderBy` argument
Sets the field the returned elements should be ordered by

#### The `handle` argument
Narrows the query results based on the global sets’ handles.

### The `queryUsers` query
This query is used to query for users.

#### The `id` argument
Narrows the query results based on the {elements}’ IDs.

#### The `uid` argument
Narrows the query results based on the {elements}’ UIDs.

#### The `status` argument
Narrows the query results based on the elements’ statuses.

#### The `archived` argument
Narrows the query results to only elements that have been archived.

#### The `trashed` argument
Narrows the query results to only elements that have been soft-deleted.

#### The `site` argument
Determines which site(s) the elements should be queried in. Defaults to the primary site.

#### The `siteId` argument
Determines which site(s) the elements should be queried in. Defaults to the primary site.

#### The `unique` argument
Determines whether only elements with unique IDs should be returned by the query.

#### The `enabledForSite` argument
Narrows the query results based on whether the elements are enabled in the site they’re being queried in, per the `site` argument.

#### The `title` argument
Narrows the query results based on the elements’ titles.

#### The `slug` argument
Narrows the query results based on the elements’ slugs.

#### The `uri` argument
Narrows the query results based on the elements’ URIs.

#### The `search` argument
Narrows the query results to only elements that match a search query.

#### The `ref` argument
Narrows the query results based on a reference string.

#### The `fixedOrder` argument
Causes the query results to be returned in the order specified by the `id` argument.

#### The `inReverse` argument
Causes the query results to be returned in reverse order.

#### The `dateCreated` argument
Narrows the query results based on the {elements}’ creation dates.

#### The `dateUpdated` argument
Narrows the query results based on the {elements}’ last-updated dates.

#### The `offset` argument
Sets the offset for paginated results.

#### The `limit` argument
Sets the limit for paginated results.

#### The `orderBy` argument
Sets the field the returned elements should be ordered by

#### The `email` argument
Narrows the query results based on the users’ email addresses.

#### The `username` argument
Narrows the query results based on the users’ usernames.

#### The `firstName` argument
Narrows the query results based on the users’ first names.

#### The `lastName` argument
Narrows the query results based on the users’ last names.


## List of available directives
Directives are not regulated by permissions and they affect how the returned data is displayed.

### The `formatDateTime` directive
This directive allows for formatting any date to the desired format. It can be applied to all fields, but changes anything only when applied to a DateTime field.

#### The `format` argument
This specifies the format to use. It defaults to the [Atom date time format](https://www.php.net/manual/en/class.datetimeinterface.php#datetime.constants.atom]).

#### The `timezone` argument
The full name of the timezone, defaults to UTC. (E.g., America/New_York)


### The `transform` directive
This directive is used to return a URL for an [asset tranform](https://docs.craftcms.com/v3/image-transforms.html). It accepts the same arguments you would use for a transform in Craft and adds the `immediately` argument.

#### The `handle` argument
The handle of the named transform to use.

#### The `width` argument
Width for the generated transform

#### The `height` argument
Height for the generated transform

#### The `mode` argument
The mode to use for the generated transform.

#### The `position` argument
The position to use when cropping, if no focal point specified.

#### The `interlace` argument
The interlace mode to use for the transform

#### The `quality` argument
The quality of the transform

#### The `format` argument
The format to use for the transform

#### The `immediately` argument
Whether the transform should be generated immediately or only when the image is requested used the generated URL



## Pre-defined interfaces
Craft defines several interfaces to be implemented by the different GraphQL types.

### The `AssetInterface` interface
This is the interface implemented by all assets.

#### The `id` field
The id of the entity

#### The `uid` field
The uid of the entity

#### The `title` field
The element’s title.

#### The `slug` field
The element’s slug.

#### The `uri` field
The element’s URI.

#### The `enabled` field
Whether the element is enabled or not.

#### The `archived` field
Whether the element is archived or not.

#### The `siteId` field
The ID of the site the element is associated with.

#### The `searchScore` field
The element’s search score, if the `search` parameter was used when querying for the element.

#### The `trashed` field
Whether the element has been soft-deleted or not.

#### The `status` field
The element's status.

#### The `dateCreated` field
The date the element was created.

#### The `dateUpdated` field
The date the element was last updated.

#### The `volumeId` field
The ID of the volume that the asset belongs to.

#### The `folderId` field
The ID of the folder that the asset belongs to.

#### The `filename` field
The filename of the asset file.

#### The `extension` field
The file extension for the asset file.

#### The `hasFocalPoint` field
Whether a user-defined focal point is set on the asset.

#### The `focalPoint` field
The focal point represented as an array with `x` and `y` keys, or null if it's not an image.

#### The `kind` field
The file kind.

#### The `size` field
The file size in bytes.

#### The `height` field
The height in pixels or null if it's not an image.

#### The `width` field
The width in pixels or null if it's not an image.

#### The `img` field
An `<img>` tag based on this asset.

#### The `url` field
The full URL of the asset.

#### The `mimeType` field
The file’s MIME type, if it can be determined.

#### The `path` field
The asset's path in the volume.

#### The `dateModified` field
The date the asset file was last modified.


### The `EntryInterface` interface
This is the interface implemented by all entries.

#### The `id` field
The id of the entity

#### The `uid` field
The uid of the entity

#### The `title` field
The element’s title.

#### The `slug` field
The element’s slug.

#### The `uri` field
The element’s URI.

#### The `enabled` field
Whether the element is enabled or not.

#### The `archived` field
Whether the element is archived or not.

#### The `siteId` field
The ID of the site the element is associated with.

#### The `searchScore` field
The element’s search score, if the `search` parameter was used when querying for the element.

#### The `trashed` field
Whether the element has been soft-deleted or not.

#### The `status` field
The element's status.

#### The `dateCreated` field
The date the element was created.

#### The `dateUpdated` field
The date the element was last updated.

#### The `sectionId` field
The ID of the section that contains the entry.

#### The `sectionHandle` field
The handle of the section that contains the entry.

#### The `typeId` field
The ID of the entry type that contains the entry.

#### The `typeHandle` field
The handle of the entry type that contains the entry.

#### The `authorId` field
The ID of the author of this entry.

#### The `author` field
The entry's author.

#### The `postDate` field
The entry's post date.

#### The `expiryDate` field
The expiry date of the entry.


### The `GlobalSetInterface` interface
This is the interface implemented by all global sets.

#### The `id` field
The id of the entity

#### The `uid` field
The uid of the entity

#### The `title` field
The element’s title.

#### The `slug` field
The element’s slug.

#### The `uri` field
The element’s URI.

#### The `enabled` field
Whether the element is enabled or not.

#### The `archived` field
Whether the element is archived or not.

#### The `siteId` field
The ID of the site the element is associated with.

#### The `searchScore` field
The element’s search score, if the `search` parameter was used when querying for the element.

#### The `trashed` field
Whether the element has been soft-deleted or not.

#### The `status` field
The element's status.

#### The `dateCreated` field
The date the element was created.

#### The `dateUpdated` field
The date the element was last updated.

#### The `name` field
The name of the global set.

#### The `handle` field
The handle of the global set.


### The `MatrixBlockInterface` interface
This is the interface implemented by all matrix blocks.

#### The `id` field
The id of the entity

#### The `uid` field
The uid of the entity

#### The `title` field
The element’s title.

#### The `slug` field
The element’s slug.

#### The `uri` field
The element’s URI.

#### The `enabled` field
Whether the element is enabled or not.

#### The `archived` field
Whether the element is archived or not.

#### The `siteId` field
The ID of the site the element is associated with.

#### The `searchScore` field
The element’s search score, if the `search` parameter was used when querying for the element.

#### The `trashed` field
Whether the element has been soft-deleted or not.

#### The `status` field
The element's status.

#### The `dateCreated` field
The date the element was created.

#### The `dateUpdated` field
The date the element was last updated.

#### The `fieldId` field
The ID of the field that owns the matrix block.

#### The `ownerId` field
The ID of the element that owns the matrix block.

#### The `typeId` field
The ID of the matrix block's type.

#### The `typeHandle` field
The handle of the matrix block's type.

#### The `sortOrder` field
The sort order of the matrix block within the owner element field.


### The `UserInterface` interface
This is the interface implemented by all users.

#### The `id` field
The id of the entity

#### The `uid` field
The uid of the entity

#### The `title` field
The element’s title.

#### The `slug` field
The element’s slug.

#### The `uri` field
The element’s URI.

#### The `enabled` field
Whether the element is enabled or not.

#### The `archived` field
Whether the element is archived or not.

#### The `siteId` field
The ID of the site the element is associated with.

#### The `searchScore` field
The element’s search score, if the `search` parameter was used when querying for the element.

#### The `trashed` field
Whether the element has been soft-deleted or not.

#### The `status` field
The element's status.

#### The `dateCreated` field
The date the element was created.

#### The `dateUpdated` field
The date the element was last updated.

#### The `friendlyName` field
The user's first name or username.

#### The `fullName` field
The user's full name.

#### The `name` field
The user's full name or username.

#### The `photo` field
The user's photo.

#### The `preferences` field
The user’s preferences.

#### The `preferredLanguage` field
The user’s preferred language.

#### The `username` field
The username.

#### The `firstName` field
The user's first name.

#### The `lastName` field
The user's last name.

#### The `email` field
The user's email.



## Interface implementation

A defined type exists for each specific interface implementation. For example, if a "News" section has "Article" and "Editorial" entry types, in addition to the `EntryInterface` interface type, two additional types would be defined the GraphQL schema, if the token used allows it: `news_article_Entry` and `news_editorial_Entry` types.

## An example query and response

### Query payload

```graphql
{
  queryEntries (section: "news", limit: 2, orderBy: "dateCreated DESC"){
    dateCreated @formatDateTime (format: "Y-m-d")
    title
    ... on news_article_Entry {
      shortDescription
      featuredImage {
        url @transform (width: 300, immediately: true)
      }
    }
  }
}
```

### The response

```json
{
  "data": {
    "queryEntries": [
      {
        "dateCreated": "2019-08-21",
        "title": "An important news item",
        "shortDescription": "<p>This is how we roll these days.</p>",
        "featuredImage": [
          {
            "url": "/assets/site/_300xAUTO_crop_center-center_none/glasses.jpg"
          }
        ]
      },
      {
        "dateCreated": "2019-07-02",
        "title": "Dolorem ea eveniet alias",
        "shortDescription": "Et omnis explicabo iusto eum nobis. Consequatur debitis architecto est exercitationem vitae velit repellendus. Aut consequatur maiores error ducimus ea et. Rem ipsa asperiores eius quas et omnis. Veniam quasi qui repellendus dignissimos et necessitatibus. Aut a illo tempora.",
        "featuredImage": []
      }
    ]
  }
}
```