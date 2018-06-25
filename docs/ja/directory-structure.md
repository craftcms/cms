# ディレクトリ構造

Craft 3 の新しいコピーをダウンロードすると、プロジェクトには次のファイルやディレクトリがあります。

#### `config/`

すべての Craft とプラグインの[設定ファイル](configuration.md)と `license.key` ファイルを保持します。

#### `modules/`

サイトで使用している [Yii modules](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules) を保持します。

#### `storage/`

ランタイムで動的に生成される一連のファイルを Craft が格納する場所です。

そこにはいくつかのフォルダが含まれます。

- `backups/` – Craft のアップデートやデータベースバックアップユーティリティの実行時に生成される、データベースのバックアップを格納します。

- `logs/` – Craft のログや PHP エラーログを格納します。

- `rebrand/` – アップロードしてある場合、カスタムログインページのロゴとサイトアイコンファイルを格納します。

- `runtime/` – ここにあるすべては、おおよそキャッシングやロギングを目的とするものです。もしフォルダが削除されたとしても、Craft の稼働に影響はありません。

   興味がある方のために、（包括的なリストではありませんが）craft/storage/runtime で確認できるものを掲載します。
   - `assets/` – 新しいサムネイルやトランスフォームの生成にあたり画像が必要なときに Craft の HTTP リクエストを節約する目的で、画像サムネイル、リサイズされたファイルのアイコン、リモートアセットボリュームに保存された画像のコピーを格納します。
   - `cache/` – データキャッシュを格納します。
   - `compiled_classes/` – いくつかの動的に定義された PHP クラスを格納します。
   - `compiled_templates/` – コンパイル済みのテンプレートを格納します。
   - `mutex/` – ファイルロックデータを格納します。
   - `temp/` – 一時ファイルを格納します。
   - `validation.key` – リクエスト間のハッシングやデータ検証に使われる、ランダムに生成され、暗号化された安全な鍵です。

#### `templates/`

フロントエンド向けのテンプレートをここにまとめます。

#### `vendor/`

これは Composer で依存関係にあるすべてのもので、Craft 自身や Composer 経由でインストールしたすべてのプラグインが含まれます。

#### `web/`

このディレクトリはウェブルートを表します。（必要ならリネームできます。）

#### `.env`

[PHP dotenv](https://github.com/vlucas/phpdotenv) の `.env` 設定ファイルです。バージョン管理でコミットする必要のない、機密性が高い、または、特定の環境に依存する設定値を定義します。

#### `.env.example`

[PHP dotenv](https://github.com/vlucas/phpdotenv) の `.env` ファイルのひな形です。実際の `.env` ファイルの出発点として使用する必要があります。ファイルとして格納されていますが、動作している Craft プロジェクトの各環境のバージョン管理からは除外してください。

#### `.gitignore`

変更をコミットするときに、無視するファイルを Git に伝えます。

#### `LICENSE.md`

`craftcms/craft` リポジトリのすべてのコードをカバーするのは標準の MIT ライセンスですが、`vendor/` フォルダの Composer がインストールしたライブラリはその限りではありません。このファイルは、自由に削除してください。

#### `README.md`

[craftcms/craft](https://github.com/craftcms/craft) リポジトリの README ファイルです。このファイルを削除するか、プロジェクトに関連性の高いコンテンツに置き換えてください。

#### `composer.json`

すべての Craft プロジェクトで使用する必要がある、出発点の `composer.json` ファイルです。

デフォルトで安全に変更したり削除できる設定には、次のものが含まれます。

- `name`
- `description`
- `keywords`
- `license`
- `homepage`
- `type`
- `support`

#### `composer.lock`

これは、`vendor/`へ現在インストールされている必要がある依存関係やバージョンを Composer へ正確に伝える Composer ファイルです。.

#### `craft`

これは、Craft のコンソールアプリケーションを起動するコマンドライン実行可能プログラムです。

#### `craft.bat`

これは `craft` 実行可能プログラムの Windows コマンドプロンプト向けラッパーです。

