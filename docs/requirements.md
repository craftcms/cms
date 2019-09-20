# Server Requirements

::: tip
You can use the [Craft Server Check](https://github.com/craftcms/server-check) script to quickly find out if your server meet’s Craft’s requirements.
:::

Craft requires the following:

* PHP 7.0+
* MySQL 5.5+ with InnoDB, MariaDB 5.5+, or PostgreSQL 9.5+
* At least 256MB of memory allocated to PHP
* At least 200MB of free disk space

## Required PHP Extensions

Craft requires the following PHP extensions:

* [ctype](https://secure.php.net/manual/en/book.ctype.php)
* [cURL](http://php.net/manual/en/book.curl.php)
* [GD](http://php.net/manual/en/book.image.php) or [ImageMagick](http://php.net/manual/en/book.imagick.php). ImageMagick is preferred.
* [iconv](http://php.net/manual/en/book.iconv.php)
* [JSON](http://php.net/manual/en/book.json.php)
* [Multibyte String](http://php.net/manual/en/book.mbstring.php)
* [OpenSSL](http://php.net/manual/en/book.openssl.php)
* [PCRE](http://php.net/manual/en/book.pcre.php)
* [PDO MySQL Driver](http://php.net/manual/en/ref.pdo-mysql.php) or [PDO PostgreSQL Driver](http://php.net/manual/en/ref.pdo-pgsql.php)
* [PDO](http://php.net/manual/en/book.pdo.php)
* [Reflection](http://php.net/manual/en/class.reflectionextension.php)
* [SPL](http://php.net/manual/en/book.spl.php)
* [Zip](http://php.net/manual/en/book.zip.php)

## Optional PHP Extensions

* [Intl](http://php.net/manual/en/book.intl.php) – Adds rich internationalization support.
* [DOM](http://php.net/manual/en/book.dom.php) - Required for parsing XML feeds as well as <api:yii\web\XmlResponseFormatter>.

## Optional PHP Methods and Configurations

Some shared hosting environments will disable certain common PHP methods and configurations that affect Craft features.

* [allow_url_fopen](http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen) - Craft requires PHP configuration to be enabled for updating and installing plugins from the Plugin Store.
* [proc_*](http://php.net/manual/en/ref.exec.php) - The PHP `proc_` methods must be enabled in order to utilize the Plugin Store and to be able to send emails.

## Required Database User Privileges

The database user you tell Craft to connect with must have the following privileges:

#### MySQL/MariaDB

* `SELECT`
* `INSERT`
* `DELETE`
* `UPDATE`
* `CREATE`
* `ALTER`
* `INDEX`
* `DROP`
* `REFERENCES`
* `LOCK TABLES`

#### PostgreSQL

* `SELECT`
* `INSERT`
* `UPDATE`
* `CREATE`
* `DELETE`
* `REFERENCES`
* `CONNECT`

## Control Panel Browser Requirements

Craft’s Control Panel requires a modern browser:

#### Windows and macOS

* Chrome 29 or later
* Firefox 28 or later
* Safari 9.0 or later
* Microsoft Edge

#### Mobile

* iOS: Safari 9.1 or later
* Android: Chrome 4.4 or later

::: tip
Craft’s Control Panel browser requirements have nothing to do with your actual website. If you’re a glutton for punishment and want your website to look flawless on IE 6, that’s your choice.
:::
