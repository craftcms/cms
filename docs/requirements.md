# Server Requirements

These are the requirements to successfully install and properly run Craft.

## Checking Your Server

Before you install Craft, it's important that you check that your server will meet the requirements. Review the requirements below or use the [Craft Server Check](https://github.com/craftcms/server-check) script to quickly check whether you meet the requirements.

_Not in charge of the server? Send a link to this page to your server administrator._

## Server Requirements

Craft requires the following:

* PHP 7.0+
* MySQL 5.5+ (with InnoDB) or PostgreSQL 9.5+
* A web server (Apache, Nginx, IIS)
* A minimum of 128MB of memory allocated to PHP
* A minimum of 200MB of free disk space

:::warning
If Craft CMS needs to process very large images or dump very large databases, you might need to bump PHP’s memory allocation up to 256MB.
:::

## Required PHP Extensions

Craft requires the following PHP extensions:

* [PCRE](http://php.net/manual/en/book.pcre.php)
* [PDO](http://php.net/manual/en/book.pdo.php)
* [PDO MySQL Driver](http://php.net/manual/en/ref.pdo-mysql.php) or [PDO PostgreSQL Driver](http://php.net/manual/en/ref.pdo-pgsql.php)
* [GD](http://php.net/manual/en/book.image.php) or [ImageMagick](http://php.net/manual/en/book.imagick.php). ImageMagick is preferred.
* [OpenSSL](http://php.net/manual/en/book.openssl.php)
* [Multibyte String](http://php.net/manual/en/book.mbstring.php)
* [JSON](http://php.net/manual/en/book.json.php)
* [cURL](http://php.net/manual/en/book.curl.php)
* [Reflection](http://php.net/manual/en/class.reflectionextension.php)
* [SPL](http://php.net/manual/en/book.spl.php)
* [Zip](http://php.net/manual/en/book.zip.php)

## Optional PHP Extensions

* [iconv](http://php.net/manual/en/book.iconv.php) – Adds support for more character encodings than PHP’s built-in [mb_convert_encoding()](http://php.net/manual/en/function.mb-convert-encoding.php) function, which Craft will take advantage of when converting strings to UTF-8.
* [Intl](http://php.net/manual/en/book.intl.php) – Adds rich internationalization support.
* [DOM](http://php.net/manual/en/book.dom.php) - Required for parsing XML feeds as well as <api:yii\web\XmlResponseFormatter>.

## Optional PHP Methods and Configurations

Some shared hosting environments will disable certain common PHP methods and configurations that affect Craft features.

* [allow_url_fopen](http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen) - Craft requires PHP configuration to be enabled for updating and installing plugins from the Plugin Store.
* [proc_*](http://php.net/manual/en/ref.exec.php) - The PHP `proc_` methods must be enabled in order to utilize the Plugin Store and to be able to send emails.

## Required Database User Privileges

The database user you tell Craft to connect with must have the following privileges:

#### MySQL

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
* `DELETE`
* `UPDATE`
* `CREATE`
* `DELETE`
* `REFERENCES`
* `CONNECT`

## CP Browser Requirements

Craft’s Control Panel requires a modern browser:

### Windows and macOS

* Chrome 29 or later
* Firefox 28 or later
* Safari 9.0 or later
* Internet Explorer 11 or later
* Microsoft Edge

### Mobile

* iOS: Safari 9.1 or later
* Android: Chrome 4.4 or later

Note: Craft’s Control Panel browser requirements have nothing to do with your actual website. If you’re a glutton for punishment and want your website to look flawless on IE 6, that’s your choice.
