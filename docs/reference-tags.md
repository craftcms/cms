# Reference Tags

Reference tags can be used to create references to various elements in your site. They can be used in any textual fields, including Text cells within a Table field.

The syntax for reference tags looks like this:

```twig
{<Type>:<Identifier>:<Property>}
```

As you can see, they are made up three segments:

1.  `<Type>` – The type of element you’re creating a reference to. This can be a fully-qualified element class name (e.g. `craft\elements\Entry`) or the element type’s “reference handle”.

    Core element types have the following reference handles:

    - `entry`
    - `asset`
    - `tag`
    - `user`
    - `globalset`

2.  `<Identifier>` – Either the element’s ID or a custom identifier supported by the element type.

    Entries support the following custom identifiers:

    - `entry-slug`
    - `sectionHandle/entry-slug`

    Identifiers can also include the site ID, UUID, or handle that the element should be loaded from, using an `@<Site>` syntax.

3.  `<Property>` _(optional)_ – The element property that the reference tag should return. If omitted, the element’s URL will be returned.

    You can refer to the element types’ class references for a list of available properties:

    - [api:craft\elements\Entry](craft\elements\Entry#public-properties)
    - [api:craft\elements\Asset](craft\elements\Asset#public-properties)
    - [api:craft\elements\Tag](craft\elements\Tag#public-properties)
    - [api:craft\elements\User](craft\elements\User#public-properties)
    - [api:craft\elements\GlobalSet](craft\elements\GlobalSet#public-properties)

    Custom field handles are also supported, for field types with values that can be represented as strings.

### Examples

The following are valid reference tags:

- `{asset:123:filename}` – returns the filename of an asset with the ID of `123` (via <api:craft\elements\Asset::getFilename()>).
- `{entry:about-us:intro}` – returns the value of an `intro` custom field on an entry with the slug `about-us`.
- `{entry:about-us@en:intro}` – returns the value of an `intro` custom field on an entry with the slug `about-us`, loaded from the site with the handle `en`.
- `{entry:blog/whats-on-tap}` – returns the URL of an entry in a `blog` section with the slug `whats-on-tap`.
- `{craft\commerce\Variant:123:price}` – returns the price of a Commerce Variant object with the id of `123`.

## Parsing Reference Tags

You can parse any string for reference tags in your templates using the [parseRefs](dev/filters.md#parserefs) filter:

```twig
{{ entry.body|parseRefs|raw }}
```
