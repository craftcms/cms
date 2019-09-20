# サーバー要件

::: tip
[Craft Server Check](https://github.com/craftcms/server-check) スクリプトを使うことで、サーバーが Craft の要件を満たしているかどうかを素早く確認できます。
:::

Craft は次の項目が必要です。

* PHP 7.0 以降
* with InnoDB の MySQL 5.5 以降、MariaDB 5.5 以降、または、PostgreSQL 9.5 以降
* 少なくとも 256MB の PHP 割当メモリ
* 少なくとも 200MB の空きディスク容量

## 必要な PHP エクステンション

Craft は次の PHP エクステンションが必要です。

* [ctype](https://secure.php.net/manual/en/book.ctype.php)
* [cURL](http://php.net/manual/en/book.curl.php)
* [GD](http://php.net/manual/en/book.image.php) または [ImageMagick](http://php.net/manual/en/book.imagick.php)。ImageMagick が好ましいです。
* [iconv](http://php.net/manual/en/book.iconv.php)
* [JSON](http://php.net/manual/en/book.json.php)
* [Multibyte String](http://php.net/manual/en/book.mbstring.php)
* [OpenSSL](http://php.net/manual/en/book.openssl.php)
* [PCRE](http://php.net/manual/en/book.pcre.php)
* [PDO MySQL Driver](http://php.net/manual/en/ref.pdo-mysql.php) または [PDO PostgreSQL Driver](http://php.net/manual/en/ref.pdo-pgsql.php)
* [PDO](http://php.net/manual/en/book.pdo.php)
* [Reflection](http://php.net/manual/en/class.reflectionextension.php)
* [SPL](http://php.net/manual/en/book.spl.php)
* [Zip](http://php.net/manual/en/book.zip.php)

## オプションの PHP エクステンション

* [Intl](http://php.net/manual/en/book.intl.php) – よりリッチな国際化のサポートを追加します。
* [DOM](http://php.net/manual/en/book.dom.php) - <api:yii\web\XmlResponseFormatter> と同様に XML フィードの解析に必要です。

## オプションの PHP メソッドと設定

一部の共用ホスティング環境では、Craft の機能に影響を与える PHP メソッドや設定が無効になっています。

* [allow_url_fopen](http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen) - アップデートやプラグインストアからのプラグインインストールを可能にするため、Craft では PHP 設定を有効にする必要があります。
* [proc_*](http://php.net/manual/en/ref.exec.php) - プラグインストアを利用したり、メールの送信ができるよう PHP の `proc_` メソッドを有効にする必要があります。

## 必要なデータベースユーザー特権

Craft のデータベースに接続するユーザーには、次の特権がなければなりません。

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

## コントロールパネルのブラウザ要件

Craft のコントロールパネルは、モダンブラウザが必要です。

#### Windows と macOS

* Chrome 29 以降
* Firefox 28 以降
* Safari 9.0 以降
* Microsoft Edge

#### モバイル

* iOS: Safari 9.1 以降
* Android: Chrome 4.4 以降

::: tip
Craft の CP のブラウザ要件は、実際のウェブサイトとは関係がありません。もしあなたがつらい仕事を苦にせず、IE 6 で完璧に表示されるサイトを望むのであれば、あたなの望む通りにできます。
:::

