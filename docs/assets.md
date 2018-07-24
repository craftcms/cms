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

## Asset Meta Fields

Each of your asset volumes has a field layout, where you can attach [fields](fields.md) that will be available to any assets within that volume. You can edit an asset volume’s field layout by clicking on the Field Layout tab when editing the volume.

Once your asset volume has some fields associated with it, you can edit the fields’ content by double-clicking on your assets, either from within the Assets index, or within an [Assets field](assets-fields.md).

## Assets Index

When you have at least one asset volume, Craft will add a new Assets item to the Control Panel's main navigation. Clicking on it will take you to the Assets Index with a list of all of your volumes in the left sidebar, and the selected volume’s files in the main content area.

From this page, you can do the following:

* Upload new files
* Rename files
* Edit files’ titles and filename
* Edit an image using the built-in image editor
* Move files to a different folder (via drag and drop)
* Delete files
* Create new subfolders (right-click on folder name)
* Rename subfolders
* Move subfolders
* Delete subfolders

## Updating Your Asset Indexes

Whenever there are files within your asset volume that were not added directly by Craft (e.g. via FTP), you will need to tell Craft to go and look for them. You can do that from the “Update Asset Indexes” tool within Settings.

## Image Transforms

Craft provides a way to perform a large variety of image transformations to your assets. See [Image Transforms](image-transforms.md) for more information.
