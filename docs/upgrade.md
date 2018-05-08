# Upgrading from Craft 2

The first step to upgrading your site to Craft 3 is updating the CMS itself.

## Preparing for the Upgrade

Before you begin, make sure that:

- you've reviewed the [changes in Craft 3](changes-in-craft-3.md)
- your server meets Craft 3’s [**minimum requirements**](requirements.md) (Craft 3 requires PHP 7+ and at least 256 MB of memory allocated to PHP)
- your site is running at least **Craft 2.6.2788**
- your **database is backed up** in case everything goes horribly wrong
- you've installed **Composer** (see step 1 of the [installation instructions](installation.md))

Once you've completed everything listed above you can continue with the upgrade process. 

## Upgrading Craft 

There are two ways you can go about updating Craft, depending on whether you want to [keep your current directory structure](#if-you-want-to-keep-your-current-directory-structure), or [switch things up](#if-you-want-your-directory-structure-to-resemble-a-new-craft-3-project) to be more like a new Craft 3 installation.

Moving to the new structure is generally recommended, for a couple reasons:

- The documentation will generally assume the new structure.
- It’s more secure, as sensitive information like database credentials are stored in `.env` files that aren’t committed to Git (utilizing [PHP dotenv](https://github.com/vlucas/phpdotenv)).
- It comes with the `craft` executable file, enabling various CLI features in your terminal. 

### If you want to keep your current directory structure…

To update Craft without making any major changes to your site’s directory structure, follow these instructions.

Note that at the end of this, your “project root” (as referenced in other areas of the documentation) will be your `craft/` directory, _not_ its parent directory.

1. Open your terminal and go to the `craft/` directory:

       cd /path/to/project/craft

2. Run the following command to load Craft 3 (this will take a few minutes):

       composer require craftcms/cms:^3.0.0

    Note: If Composer complains that your system doesn’t have PHP 7 installed, but you know it’s not an issue because Craft will run with a different PHP install (e.g. through MAMP or Vagrant), use the `--ignore-platform-reqs` flag.

3. Once all the files are in place, open your `public/index.php` file, find this line:

    ```php
    // Do not edit below this line
    ```

    …and replace everything below it with:

    ```php
    defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', realpath($craftPath));

    if (!is_dir(CRAFT_BASE_PATH.'/vendor')) {
      exit('Could not find your vendor/ folder. Please ensure that <strong><code>$craftPath</code></strong> is set correctly in '.__FILE__);
    }

    require_once CRAFT_BASE_PATH.'/vendor/autoload.php';
    $app = require CRAFT_BASE_PATH.'/vendor/craftcms/cms/bootstrap/web.php';
    $app->run();
    ```

4. Point your browser to your Control Panel URL (e.g. `http://example.dev/admin`). If you see the update prompt, you did everything right! Go ahead and click Finish Up to update your database.

5. Delete your old `craft/app/` directory. It’s no longer needed; Craft 3 is located in `vendor/craftcms/cms/` now.

> Note: If your `craft/` directory lives in a public directory on your server (e.g. within `public_html/`), you will need to make sure the new `craft/vendor/` directory is protected from web traffic. If your server is running Apache, you can do this by creating a `.htaccess` file within it, with the contents `Deny from all`.

### If you want your directory structure to resemble a new Craft 3 project…

To set your site up with the same directory structure (including the [PHP dotenv](https://github.com/vlucas/phpdotenv)-based configuration) as a brand new Craft 3 project, follow these instructions:

1. Follow steps 1 and 2 from the [Installation Instructions](installation.md). (Note that you should create your Craft 3 project in a new location; not in the same place as your Craft 2 project).

2. Configure your `.env` file with your database connection settings. You can either edit the file manually, or run the `./craft setup` command from your new root project directory in your terminal. 

  > {note} Note that the default table prefix is now blank, whereas it used to be `craft_`. Set `DB_TABLE_PREFIX="craft_"` if that’s what your tables are currently prefixed with.

3. Copy any settings from your old `craft/config/general.php` file into your new project’s `config/general.php` file.

4. Copy your old `craft/config/license.key` file into your new project’s `config/` folder.

5. Copy your old templates from `craft/templates/` over to your new project’s `templates/` directory.

6. If you had made any changes to your `public/index.php` file, copy them to your new project’s `web/index.php` file.

7. Copy any other files in your old `public/` directory into your new project’s `web/` directory.

8. Update your web server to point to your new project’s `web/` directory.

9. Point your browser to your Control Panel URL (e.g. `http://example.dev/admin`). If you see the update prompt, you did everything right! Go ahead and click “Finish up” to update your database.

Now that you have successfully upgraded your Craft 2 project to Craft 3, please take some time to review the [changes in Craft 3](changes-in-craft-3.md).
