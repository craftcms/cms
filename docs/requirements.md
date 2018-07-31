# Server Requirements

Craft requires the following:

* PHP 5.3.0+ with safe mode disabled
* MySQL 5.1.0 or later, with the InnoDB storage engine installed
* A web server (Apache, Nginx, IIS)
* A minimum of 32MB of memory allocated to PHP
* A minimum of 20MB of free disk space
* A minimum of 1MB of database space

::: tip
If you’re using MySQL 5.7.5+, give [this a read](https://craftcms.stackexchange.com/questions/12084/getting-this-sql-error-group-by-incompatible-with-sql-mode-only-full-group-by/12106) and save yourself a headache.
:::

## Required PHP Extensions

Craft requires the following PHP extensions to be enabled:

* [Reflection Extension](http://php.net/manual/en/class.reflectionextension.php)
* [PCRE Extension](http://php.net/manual/en/book.pcre.php)
* [SPL Extension](http://php.net/manual/en/book.spl.php)
* [PDO Extension](http://php.net/manual/en/book.pdo.php)
* [PDO MySQL Extension](http://php.net/manual/en/ref.pdo-mysql.php)
* [Mcrypt Extension](http://php.net/manual/en/book.mcrypt.php)
* [GD Extension](http://php.net/manual/en/book.image.php) with FreeType Support _(unless [ImageMagick Extension](http://php.net/manual/en/book.imagick.php) is installed)_
* [OpenSSL Extension](http://php.net/manual/en/book.openssl.php)
* [Multibyte String Extension](http://php.net/manual/en/book.mbstring.php)
* [JSON Extension](https://php.net/manual/en/book.json.php)
* [cURL](https://secure.php.net/manual/en/book.curl.php)
* [crypt()](https://secure.php.net/manual/en/function.crypt.php) with BLOWFISH_CRYPT enabled

## Optional PHP Extensions

* **[DOM Extension](http://php.net/manual/en/book.dom.php)** - Used to parse RSS feeds and for SVG file uploading.
* **[iconv Extension](https://secure.php.net/manual/en/book.iconv.php)** – Adds support for more character encodings than PHP’s built-in [mb_convert_encoding()](http://php.net/manual/en/function.mb-convert-encoding.php) function, which Craft will take advantage of when converting GET and POST request parameters to UTF-8.
* **[ImageMagick Extension](http://php.net/manual/en/book.imagick.php)** – Adds animated GIF support to Craft, and preserves 8-bit and 24-bit PNGs when creating image transforms, rather than converting them to 32-bit.
* **[SimpleXML](https://secure.php.net/manual/en/book.simplexml.php)** - Required if you will be using S3.

::: tip
If you’re unsure about whether your server meets the minimum requirements, just try installing Craft anyway. If there’s an issue, the installer will let you know!
:::

## Required MySQL User Privileges

The MySQL user you tell Craft to connect with must have the following privileges:

* `SELECT`
* `INSERT`
* `DELETE`
* `UPDATE`
* `CREATE`
* `ALTER`
* `INDEX`
* `DROP`
* `REFERENCES`

## CP Browser Requirements

Craft’s control panel requires a modern browser:

### Windows and OS X

* Chrome 29 or later
* Firefox 28 or later
* Safari 9.0 or later
* Internet Explorer 11 or later
* Microsoft Edge

### Mobile

* iOS: Safari 9.1 or later
* Android: Chrome 4.4 or later

::: tip
Craft’s CP browser requirements have nothing to do with your actual website. If you’re a glutton for punishment and want your website to look flawless on IE 6, that’s your choice.
:::
