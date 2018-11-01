# AssetFolderModel

AssetFolderModel objects represent the folders that [assets](../assets.md) live in.

## Simple Output

Outputting an AssetFolderModel object without attaching a property or method will return the folder’s name:

```twig
Folder: {{ image.getFolder() }}
```

## Properties

AssetFolderModel objects have the following properties:

### `id`

The folder’s ID.

### `name`

The name of the folder.

### `parent`

Alias of [getParent()](#getparent).

### `parentId`

The ID of the parent folder.

### `path`

The path to the folder.

### `sourceId`

The ID of the folder’s asset source.


## Methods

AssetFolderModel objects have the following methods:

### `getParent()`

Returns an AssetFolderModel object representing the parent folder, if there is one.
