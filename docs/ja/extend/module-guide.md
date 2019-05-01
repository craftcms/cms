# モジュールの構築方法

[[toc]]

## 準備

モジュール作成に取り組む前に、いくつかのことを決めておく必要があります。

- **名前空間** – モジュールのクラスが稼働する、ルート名前空間。（詳細については、[PSR-4](https://www.php-fig.org/psr/psr-4/) オートローディング仕様を参照してください。）これは `craft\` ではじめるべき *ではない* ことに注意してください。デベロッパーやプロジェクトを識別する何かを使用してください。
- **モジュール ID** – プロジェクト内のプラグインを一意に識別する何か。（モジュール ID は、文字ではじまり、小文字の英字、数字、および、ダッシュのみでなければなりません。`kebab-cased` にすべきです。）

::: warning
モジュール ID を選択する場合、Craft のコア[コントローラー](https://github.com/craftcms/cms/tree/develop/src/controllers)（例：`app` などの `AppController.php` と競合する）やインストールされているプラグインハンドルと競合する名前は避けてください。そうでなければ、悪いことが起こります。
:::

## 基本ファイル構造の設定

モジュールを作るため、Craft プロジェクト内のどこかに `modules/<ModuleID>/` のような新しいディレクトリを作成してください。例えば、モジュール ID が `foo` の場合、次のように設定します。

```
my-project.test/
├── modules/
│   └── foo/
│       └── Module.php
├── templates/
└── ...
```

::: tip
数クリックでモジュールの土台を作成できる [pluginfactory.io](https://pluginfactory.io/) を利用してください。
:::

## クラスのオートロードの設定

次に、プロジェクト内の `composer.json` ファイルに [`autoload`](https://getcomposer.org/doc/04-schema.md#autoload) フィールドを設定し、モジュールのクラスを見つける方法を Composer に伝える必要があります。例えば、モジュールの名前空間が `bar` で `modules/foo/` に位置している場合、次のように追加します。

```json
{
  // ...
  "autoload": {
    "psr-4": {
      "bar\\": "modules/foo/"
    }
  }
}
```

そこにモジュールを配置したら、ターミナル上でプロジェクトのディレクトリに移動し、次のコマンドを実行します。

```bash
composer dump-autoload -a
```

新しい `autoload` マッピングに基づいて、クラスオートローダースクリプトをアップデートすることを Composer に伝えます。

## アプリケーション設定のアップデート

[modules](api:yii\base\Module::modules) および [bootstrap](api:yii\base\Application::bootstrap) 配列にリストすることによって、プロジェクトの[アプリケーション設定](../config/app.md)にモジュールを追加できます。例えば、モジュール ID が `foo` でモジュールのクラス名が `foo\Module` の場合、`config/app.php` に次のように追加します。

```php
return [
    // ...
    'modules' => [
        'foo' => foo\Module::class,
    ],
    'bootstrap' => [
        'foo',
    ],
];
```

::: tip
モジュールがすべてのリクエストでロードされる必要がない場合、`bootstrap` 配列から削除できます。
:::

## モジュールクラス

`Module.php` ファイルは、システム向けのモジュールのエントリポイントです。`init()` メソッドはイベントリスナーやそれ自体の初期化を必要とする他のステップを登録するのに最適な場所です。

`Module.php` ファイルの出発点として、このテンプレートを使用してください。

```php
<?php
namespace bar;

use Craft;

class Module extends \yii\base\Module
{
    public function init()
    {
        // Define a custom alias named after the namespace
        Craft::setAlias('@bar', __DIR__);

        parent::init();

        // Custom initialization code goes here...
    }
}
```

`bar` をあなたのモジュールの名前空間に、`'@bar'` を実際の名前空間に基づく[エイリアス](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases)に置き換えてください（全ての `\` は `/` に置き換えます）。

## 参考文献

モジュールの詳細については、[Yii documentation](https://www.yiiframework.com/doc/guide/2.0/en/structure-modules) を参照してください。

