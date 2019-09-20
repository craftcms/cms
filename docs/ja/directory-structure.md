# ディレクトリ構造

Craft 3 の新しいコピーをダウンロードすると、プロジェクトには次のフォルダやファイルがあります。

### `config/`

すべての Craft とプラグインの[設定ファイル](config/README.md)と `license.key` ファイルを保持します。

::: tip
`web/index.php` に [CRAFT_CONFIG_PATH](config/php-constants.md#craft-config-path) PHP 定数を設定すると、このフォルダの名前や場所をカスタマイズできます。
:::

### `modules/`

サイトで使用している [Yii modules](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules) を保持します。

### `storage/`

ランタイムで動的に生成される一連のファイルを Craft が保管する場所です。

そこにはいくつかのフォルダが含まれます。

- `backups/` – Craft のアップデートやデータベースバックアップユーティリティの実行時に生成される、データベースのバックアップを保管します。

- `logs/` – Craft のログや PHP エラーログを保管します。

- `rebrand/` – アップロードしてある場合、カスタムログインページのロゴとサイトアイコンファイルを保管します。

- `runtime/` – ここにあるすべては、おおよそキャッシングやロギングを目的とするものです。もしフォルダが削除されたとしても、Craft の稼働に影響はありません。

   興味がある方のために、（包括的なリストではありませんが）`storage/runtime/` で確認できるものを掲載します。
   - `assets/` – 新しいサムネイルやトランスフォームの生成にあたり画像が必要なときに Craft の HTTP リクエストを節約する目的で、画像サムネイル、リサイズされたファイルのアイコン、リモートアセットボリュームに保存された画像のコピーを保管します。
   - `cache/` – データキャッシュを蓄積します。
   - `compiled_classes/` – いくつかの動的に定義された PHP クラスを保管します。
   - `compiled_templates/` – コンパイル済みのテンプレートを保管します。
   - `mutex/` – ファイルロックデータを保管します。
   - `temp/` – 一時ファイルを保管します。
   - `validation.key` – リクエスト間のハッシングやデータ検証に使われる、ランダムに生成され、暗号的に安全な鍵です。

::: tip
`web/index.php` に [CRAFT_STORAGE_PATH](config/php-constants.md#craft-storage-path) PHP 定数を設定すると、このフォルダの名前や場所をカスタマイズできます。
:::

### `templates/`

フロントエンド向けのテンプレートがここに入ります。静的に配信する画像、CSS、JS などのローカルサイトのアセットは、[web](directory-structure.md#web) フォルダに保存します。

::: tip
`web/index.php` に [CRAFT_TEMPLATES_PATH](config/php-constants.md#craft-templates-path) PHP 定数を設定すると、このフォルダの名前や場所をカスタマイズできます。
:::

### `vendor/`

これは Composer で依存関係にあるすべてのもので、Craft 自身や Composer 経由でインストールしたすべてのプラグインが含まれます。

::: tip
`web/index.php` の [CRAFT_VENDOR_PATH](config/php-constants.md#craft-vendor-path) PHP 定数を変更すると、このフォルダの名前や場所をカスタマイズできます。
:::

### `web/`

このディレクトリはサーバーのウェブルートを表します。パブリックの `index.php` ファイルがここにあり、静的に配信されるローカルサイトの画像、CSS、JS などがあります。

::: tip
このフォルダの名前や場所をカスタマイズできます。もし、他の Craft フォルダと並んでいる状態から移動するならば、`<Webroot>/index.php` の [CRAFT_BASE_PATH](config/php-constants.md#craft-vendor-path) PHP 定数を確実に更新してください。
:::

### `.env`

[PHP dotenv](https://github.com/vlucas/phpdotenv) の `.env` 設定ファイルです。バージョン管理でコミットする必要のない、機密性が高い、または、特定の環境に依存する設定値を定義します。

### `.env.example`

[PHP dotenv](https://github.com/vlucas/phpdotenv) の `.env` ファイルのひな形です。実際の `.env` ファイルの出発点として使用する必要があります。ファイルとして保存されていますが、動作している Craft プロジェクトの各環境のバージョン管理からは除外してください。

### `.gitignore`

変更をコミットするときに、無視するファイルを Git に伝えます。

### `composer.json`

すべての Craft プロジェクトで使用する必要がある、出発点の `composer.json` ファイルです。詳細については、[Composer のドキュメント](https://getcomposer.org/doc/04-schema.md) を参照してください。

### `composer.lock`

これは、`vendor/`へ現在インストールされている必要がある依存関係やバージョンを Composer へ正確に伝える Composer ファイルです。

### `craft`

これは、Craft のコンソールアプリケーションを起動するコマンドライン実行可能プログラムです。

### `craft.bat`

これは `craft` 実行可能プログラムの Windows コマンドプロンプト向けラッパーです。

