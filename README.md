# Craft Source

Craft is a CMS by Pixel & Tonic, built for people who like to take their time and do things right. You can read more about Craft at [craftcms.com](https://craftcms.com).

This repo contains the source code, resources, a custom build script and other utilities.


## Developing Locally

Craft 3’s source code can be executed by PHP directly; there is no need to run
Craft’s build script after making changes.

### Dev Server Setup

To create a dev server on your machine, follow these steps.

1. Create a “craft3.craft.dev” virtual host, and install the latest public
   version of Craft 2 on it. (Craft 3 is not yet capable of installing itself.)
2. Delete the craft/app folder.
3. Create a symlink at craft/app that points to the Source/craft/app folder
   within the repo:

         > cd path/to/craft3.craft.dev/craft
         > ln -s path/to/git/Craft/Source/craft/app

4. In your public/index.php file, add this line after setting the `$craftPath`
   variable:

   ```php
   define('CRAFT_BASE_PATH', dirname(__DIR__).'/craft/');
   ```

### PhpStorm Project Setup

To create a PhpStorm project for your dev server, follow these steps:

1. Create a new PhpStorm project in the folder containing the craft/ and
   public/ folders for craft3.craft.dev.
2. Go to Run > Edit Configurations…
3. Create a new “PHP Web Application” and call it “craft3.craft.dev”.
4. Create a new Server for your application, and call it “craft3.craft.dev”.
   Configure it with the following options:

   - Use path mappings: **Yes**
   - **craft/** should map to path/to/craft3.craft.dev/craft
   - **craft/app/** should map to path/to/git/Craft/Source/craft/app
   - **public/** should map to path/to/craft3.craft.dev/public

![PhpStorm server config](Resources/PhpStormServerConfig.png)

### Grunt Setup

If you are going to make any changes to Craft’s JavaScript or Sass files, you
will need to install Grunt and its tasks.

    > cd path/to/git/Craft
    > sudo npm install

Before making changes, start up the Grunt watcher task:

    > cd path/to/git/Craft
    > grunt watch

If you forgot to start the `grunt watch` task before making changes, you can just call `grunt` for one-off JS/CSS builds.


## Building Craft

Craft has a custom build script that does the following:

* Optionally runs any unit tests found in Source/craft/app/tests/
* Copies all files from Source/ into a build directory
* Deletes some unneeded files and folders from the build directory
* Parses all Craft PHP files for a few tokens (e.g. “@@@build@@@”)
* Creates a list of all Craft classes that should be autoload-able, and saves it in craft/app/classes.php
* Optionally copies the final craft/app/ folder to a destination of your choosing, and sets app/etc/console/yiic.php’s permissions to 777


### OS X Environment Setup

To set up your OS X environment to build Craft, follow these instructions:

1. Open ~/.bash_profile in a text editor and add the following:

        # Craft Utils environment variables
        export CRAFT_REPO_PATH=/path/to/Craft
        export CRAFT_PHP_PATH=/Applications/MAMP/bin/php/php5.3.20/bin/php
        export CRAFT_PHPINI_PATH=/Applications/MAMP/bin/php/php5.3.20/conf/php.ini
        export PATH=$CRAFT_REPO_PATH/Utils:$PATH

    (Set `CRAFT_REPO_PATH`, `CRAFT_PHP_PATH` to the actual paths of your Craft repo and PHP executable.)

2. Give the build script executable permissions by entering the following in Terminal:

        cd /path/to/Craft/Utils
        chmod 777 buildcraft

3. Restart Terminal, and try entering:

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

