# Assets

Assets are files managed by Craft. They live in “asset sources”, which represent physical folders on your server. And with Craft Pro, you can also set up Asset Sources that live on Amazon S3, Rackspace Cloud, and Google Cloud.

You can set up your asset sources from Settings → Assets. Local sources have two important settings:

* The URL to the folder containing the files
* The file system path to the folder containing the files

If you want to use a relative file system path, note that it should be relative from the directory that holds your `index.php` file. So if your files are set up like this:

    craft/
    public_html/
        index.php
        images/

…then the correct relative path to `images/` would be `images/`.

Note that Craft/PHP must be able to write to the the folder you created. See [the installation guide](installing.md#step-2-set-the-permissions) for recommended permissions.

Both of these settings are eligible for [environment-specific variables](multi-environment-configs.md#environment-specific-variables), in the event that you are sharing your Craft site between multiple environments and relative paths aren’t for you.

## Asset Meta Fields

Each of your asset sources have its own field layout, where you can attach [fields](fields.md) that will be available to any assets within that source. You can edit an asset source’s field layout by clicking on the “Field Layout” tab when editing the source.

Once your asset source has some fields associated with it, you can edit the fields’ content by double-clicking on your assets, either from within the Assets index, or within an [Assets field](assets-fields.md).

## Assets Index

When you have at least one asset source, a new “Assets” item will be added to the CP’s main nav. Clicking on that will take you to the Assets Index, which lists all of your sources in the left sidebar, and the selected source’s files in the main content area.

From this page, you can do the following:

* Upload new files
* Rename files
* Edit files’ titles
* Edit files’ content
* Move files to a different folder
* Delete files
* Create new subfolders
* Rename subfolders
* Move subfolders
* Delete subfolders

## Updating Your Asset Indexes

Whenever there are files within your asset source that were not added directly by Craft (e.g. via FTP), you will need to tell Craft to go and look for them. You can do that from the “Update Asset Indexes” tool within Settings.z

## Image Transforms

Craft provides a way to perform a large variety of image transformations to your assets. See [Image Transforms](image-transforms.md) for more information.
