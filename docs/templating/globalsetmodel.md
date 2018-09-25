# GlobalSetModel

Your templates come preloaded with GlobalSetModel objects to represent each of your site’s [global sets](../globals.md). You can access them via their handles.

## Simple Output

Outputting a GlobalSetModel object without attaching a property or method will return the global set’s name:

```twig
<h3>{{ companyInfo }}</h3>
```


## Properties

GlobalSetModel objects have the following properties:

### `cpEditUrl`

Alias of [getCpEditUrl()](#getcpediturl).

### `handle`

The global set’s handle.

### `id`

The global set’s ID.

### `locale`

The locale the global set was fetched in.

### `name`

The global set’s name.


## Methods

GlobalSetModel objects have the following methods:

### `getCpEditUrl()`

Returns the URL to the global set’s edit page within the Control Panel.
