# AssetSourceModel

AssetSourceModel objects represent your asset sources.

## Simple Output

Outputting an AssetSourceModel object without attaching a property or method will return the source’s name:

```twig
Source: {{ source }}
```

## Properties

AssetFolderModel objects have the following properties:

### `id`

The source’s ID.

### `name`

The name of the source.

### `type`

The type of source it is (`'Local'`, `'S3'`, `'Rackspace'`, or `'GoogleCloud'`).
