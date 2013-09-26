# Craft Source

Craft is a CMS by Pixel & Tonic, built for people who like to take their time and do things right. You can read more about Craft at [buildwithcraft.com](http://buildwithcraft.com).

This repo contains the source code, resources, a custom build script and other utilities.

## Building Craft

Craft has a custom build script that does the following:

* Optionally runs any unit tests found in Source/craft/app/tests/
* Copies all files from Source/ into a build directory
* Deletes some unneeded files and folders from the build directory
* Compiles all Sass files within craft/app/resources/css/ and deletes them
* Merges all Javascript files within craft/app/resources/js/classes/ into craft.js and deletes the classes/ folder
* Optionally creates compressed versions of all Javascript files within craft/app/resources/js/ within a new js/compressed/ folder
* Prepends header comments onto all Craft PHP and JS files
* Parses all Craft PHP files for a few tokens (e.g. “@@@version@@@”)
* Creates a list of all Craft classes that should be autoload-able, and saves it in craft/app/etc/config/common.php
* Optionally copies the final craft/app/ folder to a destination of your choosing, and sets app/etc/console/yiic.php’s permissions to 777


### OS X Environment Setup

To set up your OS X environment to build Craft, follow these instructions:

1. Install [Sass](http://sass-lang.com/) by opening Terminal and entering:

        gem install sass

2. Open ~/.bash_profile in a text editor and add the following:

        # Craft Utils environment variables
        export CRAFT_REPO_PATH=/path/to/Craft
        export CRAFT_PHP_PATH=/Applications/MAMP/bin/php/php5.3.20/bin/php
        export CRAFT_PHPINI_PATH=/Applications/MAMP/bin/php/php5.3.20/conf/php.ini
        export CRAFT_SASS_PATH=/usr/bin/sass
        export PATH=$CRAFT_REPO_PATH/Utils:$PATH

    (Set `CRAFT_REPO_PATH`, `CRAFT_PHP_PATH`, and `CRAFT_SASS_PATH` to the actual paths of your Craft repo, PHP executable, and Sass executable.)

3. Give the build script executable permissions by entering the following in Terminal:

        cd /path/to/Craft/Utils
        chmod 777 buildcraft

4. Restart Terminal, and try entering:

        buildcraft

    You should see a bunch of text fly by. Craft is being built!

Now that your environment is set up, you can build Craft at any time by entering `buildcraft` in Terminal.


### Options

The build script supports the following options:

* `--runtests` – Set to ‘1’ to have the build script run any tests before it starts building
* `--destdir` – Defines the directory that Craft should be built into. Defaults to a Build/ folder within the repo, which is .gitignore’d
* `--build` – The build number to be saved in craft/app/Info.php
* `--version` – The version number to be saved in craft/app/Info.php
* `--compressjs` – Set to ‘1’ to have the build script compress all of the Javascript files in craft/app/resources/js/
* `--copyappdir` – A path that the build script should copy the craft/app/ folder to once everything else is done

**Note:** Java is required for Javascript compression. If you omit `--compressjs`, you will need to add the following to your Craft install’s craft/config/general.php file:

    'useCompressedJs' => false,

## Development Guidelines

### Accessibility

* If a link is acting as a button, give it a role="button" attribute to avoid confusing screen-readers.
* Use HTML5 "placeholder" attributes for input and text fields.
* Make sure to use <label> tags.

