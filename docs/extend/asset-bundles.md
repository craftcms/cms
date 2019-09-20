# Asset Bundles

Plugins, like Craft, are supposed to be installed above the web root, which ensures that their files can’t be accessed directly via HTTP requests. Generally that’s a Very Good Thing, because it protects Craft sites from a whole host of security vulnerabilities.

There’s one case where it would be nice if HTTP requests *could* access Craft/plugin files directly though: front-end resources, such as images, CSS, and JavaScript files. Thankfully, Yii has a concept called [Asset Bundles](https://www.yiiframework.com/doc/guide/2.0/en/structure-assets) to help with this.

Asset Bundles do two things:

- They publish an inaccessible directory into a directory below the web root, making it available for front-end pages to consume via HTTP requests.
- They can register specific CSS and JS files within the directory as `<link>` and `<script>` tags in the currently-rendered page.

### Setting it Up

First establish where you want your web-publishable files to live within your plugin. Give them a directory that’s just for them. This will be the asset bundle’s **source directory**. For this example, we’ll go with `resources/`.

Then, create a file that will hold your asset bundle class. This can be located and named whatever you want. We’ll go with `FooBundle` here.

Here’s what your plugin’s structure should look like:

```
base_dir/
└── src/
    ├── FooBundle.php
    └── resources/
        ├── script.js
        ├── styles.css
        └── ...
```

Use this template as a starting point for your asset bundle class:

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
`@ns/prefix` is a placeholder for your plugin’s auto-generated [Yii alias], which will be based on your plugin’s root namespace. It represents the path to your plugin’s `src/` directory.
:::

### Registering the Asset Bundle

With that in place, all that is left is to register the asset bundle wherever its JS/CSS files are needed.

You can register it from a template using this code:

```twig
{% do view.registerAssetBundle("ns\\prefix\\FooBundle") %}
```

Or if the request is getting routed to a custom controller action, you could register it from there, before your template gets rendered:

```php
use ns\prefix\FooBundle;

public function actionFoo()
{
    $this->view->registerAssetBundle(FooBundle::class);

    return $this->renderTemplate('plugin-handle/foo');
}
```

### Getting Published File URLs

If you have a one-off file that you need to get the published URL for, but it doesn’t need to be registered as a CSS or JS file on the current page, you can use <api:craft\web\AssetManager::getPublishedUrl()>:

```php
$url = \Craft::$app->assetManager->getPublishedUrl('@ns/prefix/path/to/file.svg', true);
```

Craft will automatically publish the file for you (if it’s not published already), and return its URL.

If you want the file to be published alongside other files in the same directory, but you only want a single file’s URL, then split its path into two parts: 1) the path to the parent directory that contains all the files you want to publish; and 2) the path to the individual file you want the URL for, relative to that parent directory.

For example, if you had a bunch of icon SVG files in an `icons/` folder within your plugin, and you wanted to publish the `icons/` folder as a whole, but only needed the URL to `shaker.svg`, you would do this:

```php
$url = \Craft::$app->assetManager->getPublishedUrl('@ns/prefix/icons', true, 'shaker.svg');
```

::: tip
`@ns/prefix` is a placeholder for your plugin’s auto-generated [Yii alias], which will be based on your plugin’s root namespace. It represents the path to your plugin’s `src/` directory.
:::

[Yii alias]: https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases
