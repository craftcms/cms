# アセットバンドル

プラグインは、Craft と同様に HTTP リクエスト経由でファイルに直接アクセスできないことを保証するウェブルート上へのインストールをサポートしています。一般的に、それはとても良いことです。なぜなら、Craft サイトをあらゆるセキュリティ脆弱性から守ることができるためです。

ですが、HTTP リクエストで Craft / プラグインのファイルへ直接アクセス *できれば* 素敵なケースが1つあります。それは、画像、CSS、JavaScript ファイルのようなフロントエンドリソースです。ありがたいことに、Yii にはこれを支援するための[アセットバンドル](https://www.yiiframework.com/doc/guide/2.0/en/structure-assets)と呼ばれるコンセプトがあります。

アセットバンドルは2つのことを行います。

- ウェブルート配下のディレクトリにあるアクセスできないディレクトリを公開し、HTTP リクエスト経由でフロントエンドのページで利用できるようにします。
- 現在レンダリングされたページの `<link>` および `<script>` タグとして、ディレクトリ内の特定の CSS および JS ファイルを登録できます。

### 設定方法

はじめに、プラグイン内でウェブ公開したいファイルをどこに配置するか確定してください。公開するファイルのためだけのディレクトリを与えてください。これはアセットバンドルの**ソースディレクトリ**になります。この例では、`resources/` とします。

次に、アセットバンドルクラスを持つファイルを作成します。これは、あなたが望む場所で好きな名前にできます。ここでは `FooBundle` とします。

プラグインの構造は次のようになります。

```
base_dir/
└── src/
    ├── FooBundle.php
    └── resources/
        ├── script.js
        ├── styles.css
        └── ...
```

アセットバンドルクラスの出発点として、このテンプレートを使用してください。

```php
<?php
namespace ns\prefix;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class MyPluginAsset extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@ns/prefix/resources';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'script.js',
        ];

        $this->css = [
            'styles.css',
        ];

        parent::init();
    }
}
```

::: tip
`@ns/prefix` はプラグインのルート名前空間に基づいて、自動生成されたプラグインの [Yii alias] のためのプレースホルダーになります。プラグインの `src/` ディレクトリのパスを表します。
:::

### アセットバンドルの登録

ファイルをあるべき場所に用意したら、あとは JS / CSS ファイルを必要とされる場所でアセットバンドルに登録するだけです。

次のコードを使用して、テンプレートから登録できます。

```twig
{% do view.registerAssetBundle("ns\\prefix\\FooBundle") %}
```

または、リクエストがカスタムコントローラーアクションにルーティングされている場合、テンプレートがレンダリングされる前にそこから登録できます。

```php
use ns\prefix\FooBundle;

public function actionFoo()
{
    $this->view->registerAssetBundle(FooBundle::class);

    return $this->renderTemplate('plugin-handle/foo');
}
```

### 公開されたファイル URL の取得

公開 URL を必要とするものの、CSS や JS ファイルのように現在のページで登録する必要がないファイルがある場合、<api:craft\web\AssetManager::getPublishedUrl()> を使用できます。

```php
$url = \Craft::$app->assetManager->getPublishedUrl('@ns/prefix/path/to/file.svg', true);
```

（すでに公開されてない場合）Craft が自動的にそのファイルを公開し、URL を返します。

同じディレクトリの他のファイルと一緒に公開したいものの、単一ファイルの URL だけ必要な場合、そのパスを2つのパートに分割します。1）公開したいすべてのファイルを含む親ディレクトリのパス。2）URL を必要とする個々のファイルの親ディレクトリからの相対パス。

例えば、プラグインの `icons/` フォルダに沢山のアイコン SVG ファイル群を持ち、`icons/` フォルダ全体を公開したいものの、`shaker.svg` の URL だけが必要な場合、次のようにします。

```php
$url = \Craft::$app->assetManager->getPublishedUrl('@ns/prefix/icons', true, 'shaker.svg');
```

::: tip
`@ns/prefix` はプラグインのルート名前空間に基づいて、自動生成されたプラグインの [Yii alias] のためのプレースホルダーになります。プラグインの `src/` ディレクトリのパスを表します。
:::

[Yii alias]: https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases

