# Image Transforms

Rather than requiring that everyone upload images at a certain size, Craft lets you define “image transforms”, which set those rules on Craft’s end instead. Transforms are _non-destructive_, meaning that they have no effect on the original image that was uploaded.

## Defining Transforms from the Control Panel

You can define transforms from the Control Panel by going to Settings → Assets → Image Transforms and clicking the “New Transform” button.

Each transform has the following settings:

* **Name** – the transform’s user-facing name
* **Handle** – the transform’s template-facing handle
* **Mode** – the transform mode
* **Width** – the transform’s resulting width
* **Height** – the transform’s resulting height
* **Quality** - the transform’s resulting image quality (0 to 100)
* **Image Format** – the transform’s resulting image format

**Mode** can be set to the following values:

* **Crop** – Crops the image to the specified width and height, scaling the image up if necessary. (This is the default mode.)
* **Fit**  – Scales the image so that it is as big as possible with all dimensions fitting within the specified width and height.
* **Stretch** – Stretches the image to the specified width and height.

If **Mode** is set to “Crop”, an additional “Default Focal Point” setting will appear, where you can define which area of the image Craft should center the crop on, for images without a [focal point](assets.md#focal-points) set. Its options include:

* Top-Left
* Top-Center
* Top-Right
* Center-Left
* Center-Center
* Center-Right
* Bottom-Left
* Bottom-Center
* Bottom-Right

If you leave either **Width** or **Height** blank, that dimension will be set to whatever maintains the image’s aspect ratio. So for example, if you have an image that is 600 by 400 pixels, and you set a transform’s Width to 60, but leave Height blank, the resulting height will be 40.

If you leave **Quality** blank, Craft will use the quality set by your <config:defaultImageQuality> config setting.

**Image Format** can be set to the following values:

* jpg
* png
* gif

If you leave **Image Format** blank, Craft will use the original image’s format if it’s web-safe (.jpg, .png, or .gif); otherwise it will try to figure out the best-suited image format for the job. If it can’t determine that (probably because ImageMagik isn’t installed), it will just go with .jpg.

### Applying CP-defined Transforms to Images

To output an image with a transform applied, simply pass your transform’s handle into your asset’s [getUrl()](api:craft\elements\Asset::getUrl()), [getWidth()](api:craft\elements\Asset::getWidth()), and [getHeight()](api:craft\elements\Asset::getHeight()) functions:

```twig
<img src="{{ asset.getUrl('thumb') }}" width="{{ asset.getWidth('thumb') }}" height="{{ asset.getHeight('thumb') }}">
```

## Defining Transforms in your Templates

You can also define transforms directly in your templates.

First, you must create an object that defines the transform’s parameters:

```twig
{% set thumb = {
    mode: 'crop',
    width: 100,
    height: 100,
    quality: 75,
    position: 'top-center'
} %}
```

Then you can pass that object into your AssetFileModel’s `getUrl()`, `getWidth()`, and `getHeight()` functions:

```twig
<img src="{{ asset.getUrl(thumb) }}" width="{{ asset.getWidth(thumb) }}" height="{{ asset.getHeight(thumb) }}">
```

Note how in that example there are no quotes around “`thumb`”, like there were in the first one. That’s because in the first one, we were passing a _string_ set to a CP-defined transform’s handle, whereas in this example we’re passing a _variable_ referencing the ‘thumb’ object we created within the template.

### Possible Values

All of the same settings available to CP-defined transforms are also available to template-defined transforms.

* The `mode` property can be set to either `'crop'`, `'fit'`, or `'stretch'`.
* If `mode` is set to `'crop'`, you can pass a `position` property, set to either `'top-left'`, `'top-center'`, `'top-right'`, `'center-left'`, `'center-center'`, `'center-right'`, `'bottom-left'`, `'bottom-center'`, or `'bottom-right'`.
* `width` and `height` can be set to integers or omitted.
* `quality` can be set to a number between 0 and 100, or omitted.
* `format` can be set to `'jpg'`, `'gif'`, `'png'`, or omitted.
