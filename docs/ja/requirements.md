# サーバー要件

これらは Craft を正常にインストールし、適切に動作させるための要件です。

## サーバーの確認

Craft をインストールする前に、サーバーが要件を満たしているか調べることは重要です。以下の要件を再確認してください。または、要件を満たすかどうかを素早くチェックできる [Craft Server Check](https://github.com/craftcms/server-check) スクリプトを使ってください。

_サーバー担当ではありませんか？このページのリンクをサーバー管理者に送ってください。_

## サーバー要件

Craft は次の項目が必要です。

* PHP 7.0 以降
* MySQL 5.5 以降（with InnoDB）または PostgreSQL 9.5 以降
* ウェブサーバー（Apache、Nginx、IIS）
* 最低256MBの PHP 割当メモリ
* 最低200MBの空きディスク容量

## 必要な PHP エクステンション

Craft は次の PHP エクステンションが必要です。

* [PCRE](http://php.net/manual/en/book.pcre.php)
* [PDO](http://php.net/manual/en/book.pdo.php)
* [PDO MySQL Driver](http://php.net/manual/en/ref.pdo-mysql.php) または [PDO PostgreSQL Driver](http://php.net/manual/en/ref.pdo-pgsql.php)
* [GD](http://php.net/manual/en/book.image.php) または [ImageMagick](http://php.net/manual/en/book.imagick.php)。ImageMagick が好ましいです。
* [OpenSSL](http://php.net/manual/en/book.openssl.php)
* [Multibyte String](http://php.net/manual/en/book.mbstring.php)
* [JSON](https://php.net/manual/en/book.json.php)
* [cURL](http://us1.php.net/manual/en/book.curl.php)
* [Reflection](http://php.net/manual/en/class.reflectionextension.php)
* [SPL](http://php.net/manual/en/book.spl.php)
* [Zip](https://secure.php.net/manual/en/book.zip.php)

さらに、Craft がメール送信できるよう PHP の [proc_*](https://secure.php.net/manual/en/ref.exec.php) メソッドを有効にする必要があります。

## オプションの PHP エクステンション

* [iconv](http://us1.php.net/manual/en/book.iconv.php) – Craft が文字列を UTF-8 に変換する際に利用され、PHP にビルドインされている [mb_convert_encoding()](http://php.net/manual/en/function.mb-convert-encoding.php) よりも多くの文字エンコーディングのサポートを追加します。
* [Intl](http://php.net/manual/en/book.intl.php) – よりリッチな国際化のサポートを追加します。
* [DOM](http://php.net/manual/en/book.dom.php) - <api:yii\web\XmlResponseFormatter> と同様に XML フィードの解析に必要です。

## 必要なデータベースユーザー特権

Craft のデータベースに接続するユーザーには、次の特権がなければなりません。

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

## CP のブラウザ要件

Craft のコントロールパネルは、モダンブラウザが必要です。

### Windows と macOS

* Chrome 29 以降
* Firefox 28 以降
* Safari 9.0 以降
* Internet Explorer 11 以降
* Microsoft Edge

### モバイル

* iOS: Safari 9.1 以降
* Android: Chrome 4.4 以降

メモ：Craft の CP のブラウザ要件は、実際のウェブサイトとは関係がありません。もしあなたがつらい仕事を苦にせず、IE 6 で完璧に表示されるサイトを望むのであれば、あたなの望む通りにできます。

