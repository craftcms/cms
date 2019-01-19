# Upgrading from Craft 2

The first step to upgrading your site to Craft 3 is updating the CMS itself.

## Preparing for the Upgrade

Before you begin, make sure that:

- you've reviewed the [changes in Craft 3](changes-in-craft-3.md)
- your server meets Craft 3’s [minimum requirements](requirements.md) (Craft 3 requires PHP 7+ and at least 256 MB of memory allocated to PHP)
- your site is running at least **Craft 2.6.2788**
- your plugins are all up-to-date, and you’ve verified that they’ve been updated for Craft 3 (you can see a report of your plugins’ Craft 3 compatibility status from the Updates page in the Craft 2 Control Panel)
- your **database is backed up** in case everything goes horribly wrong

Once you've completed everything listed above you can continue with the upgrade process.

## Performing the Upgrade

The best way to upgrade a Craft 2 site is to approach it like you’re building a new Craft 3 site. So to begin, create a new directory alongside your current project, and follow steps 1-3 in the [installation instructions](installation.md).

With Craft 3 downloaded and prepped, follow these steps to complete the upgrade: 

1. Configure the `.env` file in your new project with your database connection settings from your old `craft/config/db.php` file.

   ::: tip
   Don’t forget to set `DB_TABLE_PREFIX="craft"` if that’s what your database tables are prefixed with.
   :::

2. Copy any settings from your old `craft/config/general.php` file into your new project’s `config/general.php` file.

3. Copy your old `craft/config/license.key` file into your new project’s `config/` folder.

4. Copy your old custom Redactor config files from `craft/config/redactor/` over to your new project’s `config/redactor/` directory.

5. Copy your old custom login page logo and site icon files from `craft/storage/rebrand/` over to your new project’s `storage/rebrand/` directory.

6. Copy your old user photos from `craft/storage/userphotos/` over to your new project’s `storage/userphotos/` directory.

7. Copy your old templates from `craft/templates/` over to your new project’s `templates/` directory.

8. If you had made any changes to your `public/index.php` file, copy them to your new project’s `web/index.php` file.

9. Copy any other files in your old `public/` directory into your new project’s `web/` directory.

10. Update your web server to point to your new project’s `web/` directory.

11. Point your browser to your Control Panel URL (e.g. `http://my-project.test/admin`). If you see the update prompt, you did everything right! Go ahead and click “Finish up” to update your database.

12. If you had any plugins installed, you’ll need to install their Craft 3 counterparts from the “Plugin Store” section in the Control Panel. (See the plugins’ documentation for any additional upgrade instructions.)

Now that you have successfully upgraded your Craft 2 project to Craft 3, please take some time to review the [changes in Craft 3](changes-in-craft-3.md).

## Troubleshooting

#### I get the Craft installer when I access my Control Panel.

If this happens, it’s because your database connection settings in the `.env` file don’t quite match up to what they used to be. Most likely you forgot to set the correct `DB_TABLE_PREFIX`.

#### I’m getting a “Setting unknown property: craft\config\DbConfig::initSQLs” error.

The `initSQLs` database config setting was removed in Craft 3, as it was generally only used to fix MySQL 5.7 support in Craft 2, which isn’t necessary in Craft 3. Just open your `config/db.php` file and delete the line that begins with `'initSQLs'`.
