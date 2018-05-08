# Assets

Assets are files managed by Craft. They live in Asset Volumes, which represent physical directories. 

The default installation of Craft only supports local volumes (directories on the same server on which Craft is running) but you can also set up remote Asset Volumes that live on [Amazon S3](), [Rackspace Cloud](), and [Google Cloud]() using one of the first-party plugins.

You can set up your asset volumes from Settings → Assets. 

Local volumes have two important settings:

* The URL to the directory containing the files, assuming the files are meant to be publicly accessed
* The file system path to the directory containing the files

If you want to use a relative file system path, note that it should be relative from the directory that holds your `index.php` file. So if your files are set up like this:

    craft/
	web/
		index.php
		images/

…then the correct relative path to `images/` would be `images/`.

Note that Craft/PHP must be able to write to the directory you created.

::: tip
You can override the Volume settings using the `config/volumes.php` configuration file. This file is not in the default Craft installation so you may have to create it. Learn more about what need to go in the `volumes.php` file in the [Overriding Volume Settings](configuration.md#overriding-volume-settings) section in the Configuration documentation.
:::

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
