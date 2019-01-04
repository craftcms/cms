# Assets

You can manage your project’s media and document files (“assets”) in Craft just like entries and other content types.

## Volumes

All of your assets live in “volumes”. Volumes are storage containers. A volume can be a directory on the web server, or a remote storage service like Amazon S3.

You can manage your project’s volumes from Settings → Assets.

All volumes let you choose whether the assets within them should have public URLs, and if so, what their **Base URL** should be. The Base URL can begin with an [alias](config/README.md#aliases) such as `@web`, which represents the URL to the directory that contains the `index.php` file. For example, if your local volume directory is located at `web/assets/images/`, you could set the Base URL to `@web/assets/images`.

### Local Volumes

Out of the box, you can create one type of volume, “Local”. Local volumes represent a directory on the local web server.

Local volumes have one setting, **File System Path**. Use this setting to define the path to the volume’s root directory on the server. This path can begin with an [alias](config/README.md#aliases) such as `@webroot`, which represents the path to the directory that contains the `index.php` file. For example, if your local volume directory is located at `web/assets/images/`, you could set the File System Path to `@webroot/assets/images`.

Note that Craft/PHP must be able to write to the directory you created.

::: tip
You can override the Volume settings using the `config/volumes.php` configuration file. This file is not in the default Craft installation so you may have to create it. Learn more about what need to go in the `volumes.php` file in the [Overriding Volume Settings](config/README.md#overriding-volume-settings) section in the Configuration documentation.
:::

### Remote Volumes

If you would prefer to store your assets on a remote storage service like Amazon S3, you can install a plugin that provides the integration.

- [Amazon S3](https://github.com/craftcms/aws-s3) (first party)
- [Google Cloud Storage](https://github.com/craftcms/google-cloud) (first party)
- [Rackspace Cloud Files](https://github.com/craftcms/rackspace) (first party)
- [DigitalOcean Spaces](https://github.com/vaersaagod/dospaces) (Værsågod)
- [fortrabbit Object Storage](https://github.com/fortrabbit/craft-object-storage) (fortrabbit)

## Asset Custom Fields

Each of your volumes has a field layout, where you can set the [fields](fields.md) that will be available to assets within that volume. You can edit a volume’s field layout by clicking on the Field Layout tab when editing the volume.

Any fields you select here will be visible in the asset editor HUD that opens up when you double-click on an asset (either on the [Assets page](#assets-page) or from [Assets fields](assets-fields.md).

## Assets Page

When you create your first volume, an “Assets” item will be added to the main Control Panel navigation. Clicking on it will take you to the Assets page, which shows a list of all of your volumes in the left sidebar, and the selected volume’s files in the main content area.

From this page, you can do the following:

- Upload new files
- Rename files
- Edit files’ titles and filenames
- Launch the Image Editor for a selected image
- Manage subfolders
- Move files to a different volume or subfolder (via drag and drop)
- Delete files

### Managing Subfolders

You can create a subfolder in one of your volumes by right-clicking on the volume in the left sidebar, and then choosing “New subfolder”.

Once you’ve created a subfolder, you can start dragging files into it.

You can create a nested subfolder within a subfolder by right-clicking on the subfolder in the left sidebar, and then choosing “New subfolder”.

You can rename a subfolder by right-clicking on the subfolder in the left sidebar, and then choosing “Rename folder”.

You can delete a subfolder (and all assets within it) by right-clicking on the subfolder in the left sidebar, and then choosing “Delete folder”.

## Updating Asset Indexes

If any files are ever added, modified, or deleted outside of Craft (such as over FTP), you will need to tell Craft to update its indexes for the volume. You can do that from Utilities → Asset Indexes.

You will have the option to cache remote images. If you don’t have any remote volumes (Amazon S3, etc.), you can safely ignore it. Enabling the setting will cause the indexing process to take longer to complete, but it will improve the speed of [image transform](image-transforms.md) generation.

## Image Transforms

Craft provides a way to perform a variety of image transformations to your assets. See [Image Transforms](image-transforms.md) for more information.

## Image Editor

Craft provides a built-in Image Editor for making changes to your images. You can crop, straighten, rotate, and flip your images, as well as choose a focal point on them.

To launch the Image Editor, double-click on an image (either on the Assets page or from an [Assets field](assets-fields.md)) and click on the “Edit” button in the top-right of the image preview area in the HUD. Alternatively, you can select an asset on the [Assets page](#assets-page) and then choose “Edit image” from the task menu (gear icon).

### Focal Points

Set focal points on your images so Craft knows which part of the image to prioritize when determining how to crop your images for [image transforms](image-transforms.md). Focal points take precedence over the transform’s Crop Position setting.

To set a focal point, open the Image Editor and click on the Focal Point button. A circular icon will appear in the center of your image. Drag it to wherever you want the image’s focal point to be.

To remove the focal point, click on the Focal Point button again.

Like other changes in the Image Editor, focal points won’t take effect until you’ve saved the image.
