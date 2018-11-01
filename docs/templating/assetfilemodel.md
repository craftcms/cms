# AssetFileModel

Whenever you’re dealing with an [asset](../assets.md) in your template, you’re actually working with an AssetFileModel object.

## Simple Output

Outputting an AssetFileModel object without attaching a property or method will return the asset’s Title:

```twig
<a href="{{ file.url }}">{{ file }}</a>
```

## Properties

AssetFileModel objects have the following properties:

### `dateCreated`

A [DateTime](datetime.md) object of the date the asset was created.

### `dateUpdated`

A [DateTime](datetime.md) object of the date the asset was last updated.

### `extension`

Alias of [getExtension()](#getextension).

### `filename`

The name of the file.

### `folder`

Alias of [getFolder()](#getfolder).

### `folderId`

The ID of the folder that the file lives in.

### `height`

Alias of [getHeight()](#getheight).

### `id`

The file’s ID.

### `img`

Alias of [getImg()](#getimg).

### `kind`

The kind of file it is.

The possible values are:

* access
* audio
* compressed
* excel
* flash
* html
* illustrator
* image
* pdf
* photoshop
* php
* powerpoint
* text
* video
* word

### `locale`

The locale the asset was fetched in.

### `link`

Alias of [getLink()](#getlink).

### `mimeType`

Alias of [getMimeType()](#getmimetype).

### `next`

Alias of [getNext()](#getnext).

### `prev`

Alias of [getPrev()](#getprev).

### `size`

The size of the file in bytes. You can output it as a formatted filesize using Craft’s [filesize](filters.md#filesize) filter.

```twig
{{ file.size|filesize }}
```

### `source`

Alias of [getSource()](#getsource).

### `sourceId`

The ID of the file’s asset source.

### `title`

The file’s title.

### `url`

Alias of [getUrl()](#geturl).

### `width`

Alias of [getWidth()](#getwidth).


## Methods

AssetFileModel objects have the following methods:

### `getExtension()`

Returns the file extension, if there is one.

### `getFolder()`

Returns an [AssetFolderModel](assetfoldermodel.md) object with info about the folder the file lives in.

### `getHeight( transform )`

If the file is an image, this returns the image’s height.

You may optionally pass in a transform handle/object to get the height of the image for the given transform. (See [image-transforms](../image-transforms.md) for more info.)

If you’ve already set a default transform via [setTransform()](#settransform) and you wish to get the original image height, you can pass in `false` instead.

### `getImg()`

Returns an `<img>` tag with the `src` attribute set to the asset’s URL, the `width` and `height` attributes set to the asset’s width and height, and the `alt` attribute set to the asset’s title.

### `getLink()`

Returns an `<a>` tag, set to the asset’s URL, and using the asset’s title as the text.

### `getMimeType()`

Returns the MIME type of the file.

### `getNext( params )`

Returns the next asset that should show up in a list based on the parameters entered. This function accepts either a `craft.assets` variable (sans output function), or a parameter array.

### `getPrev( params )`

Returns the previous asset that would have shown up in a list based on the parameters entered. This function accepts either a `craft.assets` variable (sans output function), or a parameter array.

### `getSource()`

Returns an [AssetSourceModel](assetsourcemodel.md) object representing the asset’s source.

### `getUrl( transform )`

Returns the image’s URL.

You may optionally pass in a transform handle/object to get the url of the image for the given transform. (See [image-transforms](../image-transforms.md) for more info.)

If you’ve already set a default transform via [setTransform()](#settransform) and you wish to get the original image URL, you can pass in `false` instead.

### `getWidth( transform )`

If the file is an image, this returns the image’s width.

You may optionally pass in a transform handle/object to get the width of the image for the given transform. (See [image-transforms](../image-transforms.md) for more info.)

If you’ve already set a default transform via [setTransform()](#settransform) and you wish to get the original image width, you can pass in `false` instead.

### `setTransform( transform )`

Sets the default transform that should be used by [getWidth()](#getwidth), [getHeight()](#getheight), and [getUrl()](#geturl), if no alternate transform is provided to those functions. You can pass in either an asset transform’s handle (as a string), or an object defining the transform.

```twig
{% set transform = {
    mode: 'crop',
    width: 100,
    height: 100
} %}

{% do image.setTransform(transform) %}

<img src="{{ image.url }}" width="{{ image.width }}" height="{{ image.height }}">
```
