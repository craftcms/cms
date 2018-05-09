# `craft.sections`

You can get info about your site’s sections using `craft.sections`.

## Methods

The following methods are available:

### `getAllSections()`

Returns an array of [SectionModel](sectionmodel.md) objects representing each of your site’s sections.

```twig
{% set sections = craft.sections.getAllSections() %}
```

### `getEditableSections()`

Returns an array of [SectionModel](sectionmodel.md) objects representing each of your site’s sections, which the current user has permission to edit entries in.

```twig
{% set sections = craft.sections.getEditableSections() %}
```

### `getTotalSections()`

Returns the total number of sections your site has.

```twig
{% set total = craft.sections.getTotalSections() %}
```

### `getTotalEditableSections()`

Returns the total number of sections your site has, which the current user has permission to edit entries in.

```twig
{% set total = craft.sections.getTotalEditableSections() %}
```

### `getSectionById( sectionId )`

Returns a [SectionModel](sectionmodel.md) object representing a section in your site, by its ID.

```twig
{% set sectionId = craft.request.getSegment(3) %}
{% set section = craft.sections.getSectionById(sectionId) %}
```

### `getSectionByHandle( handle )`

Returns a [SectionModel](sectionmodel.md) object representing a section in your site, by its handle.

```twig
{% set handle = craft.request.getSegment(3) %}
{% set section = craft.sections.getSectionByHandle(handle) %}
```
