# SectionModel

Whenever you’re dealing with a [section](../sections-and-entries.md#sections) in your template, you’re actually working with a SectionModel object.

## Simple Output

Outputting a SectionModel object without attaching a property or method will return the section’s name:

```twig
<h3>{{ section }}</h3>
```


## Properties

SectionModel objects have the following properties:

### `enableVersioning`

Whether versioning is enabled for entries in this section.

### `handle`

The handle of the section.

### `hasUrls`

Whether the section is set to give its entries their own URLs.

### `id`

The ID of the section.

### `maxLevels`

The maximum number of levels the section’s entries can be nested, if it’s a Structure section.

### `name`

The name of the section.

### `template`

The template path that Craft should load when its entries’ URLs are requested.

### `type`

The type of section it is (`single`, `channel`, or `structure`).


## Methods

SectionModel objects have the following methods:

### `getEntryTypes()`

Returns an array of [EntryTypeModel](entrytypemodel.md) objects representing each of the section’s entry types.

### `getUrlFormat()`

Returns the section’s URL format (or URL) for the current locale.

### `isHomepage()`

Whether it’s a Single section, set to be the site’s homepage.
