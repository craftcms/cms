# Updating

## One-click Updating

When an update is available, users with the permission to update Craft will see a badge in the CP header. Clicking on that badge will take you to a page that shows the release notes of all the available updates.

You can get to that page at any time by clicking the “Check for updates” link in the CP footer as well. Whenever you go to the page, whether Craft thinks updates are available or not, the page will clear its cache and re-check for available updates.

At the top of the page there is an “Update” button. Clicking that will initiate Craft’s self-updating process.

For one-click updates to work, your craft/app folder and all its enclosed files and folders must be writable. The exact permissions you should use depend on the relationship between the user that Apache/PHP is running as and the user who actually owns the craft/config folder.

Here are some recommended permissions depending on that relationship:

* If they are the same user, use 744.
* If they're in the same group, then use 774.
* Otherwise, use 777.


## Manually Updating

The “Update” button on the Updates page has a context menu with an alternative “Download” option. Clicking on that will give you the same Craft.zip file you could have gotten by downloading Craft from [craftcms.com](https://craftcms.com).

When Craft.zip has finished downloading, you can manually update your Craft install by replacing the old craft/app folder with the new one.

If you’re manually updating a live site, we recommend you follow these instructions to minimize the time your site is down:

1. Rename the craft/app/ folder in the release to “app-new”.
2. Upload craft/app-new to the craft folder on your server, alongside the old craft/app folder.
3. Once app-new is done uploading, your FTP client may have uploaded the app-new folder with different permissions that what your craft/app folder currently has. If so, make sure app-new matches what craft/app currently has.
4. Rename the old craft/app folder to craft/app-old.
5. Rename app-new to “app”.
6. Point your browser to your Craft control panel. If the update needs to run any new database migrations, you will be prompted to proceed with a database update. Click “Finish up” and let the database updates run.
7. Delete the app-old folder.