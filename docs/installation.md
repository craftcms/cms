# Installation Instructions

[[toc]]

## Step 1: Download Craft

Craft can be downloaded with [Composer](#downloading-with-composer) or by [manually downloading](#downloading-an-archive-file-manually) a zip or tar.gz archive. The end result will be the same, so go with whichever route you feel more comfortable with.

### Downloading with Composer

::: tip
You should be running Composer 1.3.0 or later. You can find out your installed version of Composer by running `composer -V` from your terminal. If that outputs something lower than 1.3.0, run `composer self-update` to update your Composer installation.
:::

To create a new Craft project, run this command (substituting `<Path>` with the path where Composer should create the project):

```bash
composer create-project craftcms/craft <Path>
```

Composer will take a couple minutes to load everything. Once it’s done you will be shown a success message:

![The success message shown after loading Craft with Composer](./images/installation-command-line.png)

### Downloading an Archive File Manually

Download the archive format you prefer to work with:

- **zip**: [3.0.40](https://download.craftcdn.com/craft/3.0/Craft-3.0.40.zip), [latest](https://craftcms.com/latest-v3.zip)
- **tar.gz**: [3.0.40](https://download.craftcdn.com/craft/3.0/Craft-3.0.40.tar.gz), [latest](https://craftcms.com/latest-v3.tar.gz)

Extract the archive wherever you want your new Craft project to live.

::: tip
If you’re on macOS, be careful not to lose the hidden files in there (`.env`, `.env.example`, `.gitignore`, and `web/.htaccess`). You can press <kbd>Command</kbd>+<kbd>Shift</kbd>+<kbd>.</kbd> to toggle hidden file visibility in Finder.
:::

### Directory Structure

Once Craft’s files are in place, your project directory should have a directory structure like this:

```
my-project.test/
├── config/
│   └── ...
├── storage/
│   └── ...
├── templates/
│   └── ...
├── vendor/
│   └── ...
├── web/
│   └── ...
├── .env
├── .env.example
├── composer.json
├── craft
└── craft.bat
```

::: tip
If you want to learn about what all these folders and files are for and how to customize them, visit the [Directory Structure](directory-structure.md) page.
:::

## Step 2: Set the File Permissions

::: tip
If you used Composer to download Craft, you can probably safely skip this step.
:::

For Craft to run properly, PHP needs to be able to write to the following places:

- `.env`
- `composer.json`
- `composer.lock`
- `config/license.key`
- `storage/*`
- `vendor/*`
- `web/cpresources/*`

The exact permissions you should be setting depends on the relationship between the system user that PHP is running as, and who owns the actual folders/files.

- If they are the same user, use `744`.
- If they’re in the same group, then use `774`.
- If you’re not sure and like to live life on the edge, use `777`.

::: warning HEY IIS FANS
Make sure your site’s AppPool account has write permissions to these folders/files as well.
:::

## Step 3: Set a Security Key

::: tip
If you used Composer to download Craft, you can probably safely skip this step.
:::

Each Craft project should have a unique security key, which is shared between each of the environments that the project is installed on.

You can generate and assign the key [manually](#set-the-key-manually), or have Craft do it for you with a [terminal command](#set-the-key-from-your-terminal).

### Set the Key Manually

First generate a cryptographically secure key, preferably using a password generator like [1Password](https://1password.com). (There’s no length limit.)

Then open up your `.env` file (you may need to use an app like [Transmit](https://panic.com/transmit/) to do this if you’re running macOS), and find this line:

    SECURITY_KEY=""

Paste your security key inside the quotes and save the file.

### Set the Key from Your Terminal

In your terminal, go to your project’s root directory and run the following command:

```bash
./craft setup/security-key
```

## Step 4: Create a Database

Next up, you need to create a database for your Craft project. Craft 3 supports both MySQL 5.5+ and PostgreSQL 9.5+.

If you’re given a choice, we recommend the following database settings in most cases:

- **MySQL**
  - Default Character Set: `utf8`
  - Default Collation: `utf8_unicode_ci`

- **PostgreSQL**
  - Character Set: `UTF8`

## Step 5: Set up the Web Server

Create a new web server to host your Craft project. Its document root should point to your `web/` directory (or whatever you’ve renamed it to).

If you’re not using [MAMP](https://mamp.info) or another localhosting tool, you will probably need to update your `hosts` file, so your computer knows to route requests to your chosen host name to the local computer.

- **macOS/Linux/Unix**: `/etc/hosts`
- **Windows**: `\Windows\System32\drivers\etc\hosts`

You can test whether you set everything up correctly by pointing your web browser to `http://<Hostname>/index.php?p=admin/install` (substituting `<Hostname>` with your web server’s host name). If Craft’s Setup Wizard is shown, the host name is correctly resolving to your Craft installation.

## Step 6: Run the Setup Wizard

Finally, it’s time to run Craft’s Setup Wizard. You can either run that from your [terminal](#terminal-setup) or your [web browser](#web-browser-setup).

### Terminal Setup

In your terminal, go to your project’s root directory and run the following command to kick off the Setup Wizard:

```bash
./craft setup
```

The command will ask you a few questions to learn how to connect to your database, and then kick off Craft’s installer. Once it’s done, you should be able to access your new Craft site from your web browser.

### Web Browser Setup

In your web browser, go to `http://<Hostname>/index.php?p=admin/install` (substituting `<Hostname>` with your web server’s host name). If you’ve done everything right so far, you should be greeted by Craft’s Setup Wizard.

![Craft Installation Screen](./images/installation-step-0.png)

The first step of the installer is to accept the [license agreement](https://craftcms.com/license). Scroll down through the agreement (reading it all, of course) and click the “Got it” button to accept.

![Craft Installation License Agreement](./images/installation-step-1.png)

The second step is to enter your database connection information.

::: tip
If the Setup Wizard skips this step, it’s because Craft is already able to connect to your database.
:::

![Craft Installation Database Connection Information](./images/installation-step-2.png)

The third step of the installer is to create an admin account. Don’t be one of _those people_ and be sure to pick a strong password.

![Craft Installation Create User Account](./images/installation-step-3.png)

The final step is to define your System Name, Base URL, and Language.

![Craft Installation System Settings](./images/installation-step-4.png)

Click “Finish up” to complete the setup process. A few seconds later, you should have a working Craft install!

If it was successful, Craft will redirect your browser to the Control Panel.

![Craft Installation Complete](./images/installation-step-5.png)

Congratulations, you’ve just installed Craft!

Now build something incredible.
