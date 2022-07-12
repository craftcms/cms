<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\pluginstore;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;
use craft\web\View;

/**
 * Asset bundle for the Plugin Store page
 */
class PluginStoreAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->css = [
            'css/app.css',
        ];

        $this->js = [
            'js/app.js',
        ];

        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'Abandoned',
                'Active Trials',
                'Active installs',
                'Active trials added to the cart.',
                'Add all to cart',
                'Add to cart',
                'Added to cart',
                'Already in your cart',
                'Ascending',
                'Buy now',
                'Cart',
                'Categories',
                'Changelog',
                'Checkout',
                'Compatibility',
                'Copy the package’s name for this plugin.',
                'Couldn’t add all items to the cart.',
                'Couldn’t load CMS editions.',
                'Couldn’t load active trials.',
                'Critical',
                'Descending',
                'Documentation',
                'For when you’re building a website for yourself or a friend.',
                'For when you’re building something professionally for a client or team.',
                'Free',
                'Information',
                'Install',
                'Installed as a trial',
                'Installed',
                'Item',
                'Items in your cart',
                'Last Update',
                'Last update',
                'Less',
                'License',
                'Licensed',
                'Loading Plugin Store…',
                'More',
                'Name',
                'No results.',
                'Only up to {version} is compatible with your version of Craft.',
                'Package Name',
                'Page not found.',
                'Plugin Store',
                'Popularity',
                'Price includes 1 year of updates.',
                'Reactivate',
                'Remove',
                'Report an issue',
                'Search plugins',
                'See all',
                'Showing results for “{searchQuery}”',
                'The Plugin Store is not available, please try again later.',
                'The developer recommends using <a href="{url}">{name}</a> instead.',
                'This license is tied to another Craft install. Visit {accountLink} to detach it, or buy a new license.',
                'This plugin is no longer maintained.',
                'This plugin isn’t compatible with your version of Craft.',
                'This plugin requires PHP {v1}, but your composer.json file is currently set to {v2}.',
                'This plugin requires PHP {v1}, but your environment is currently running {v2}.',
                'Total Price',
                'Try for free',
                'Try',
                'Updates until {date} ({sign}{price})',
                'Updates until {date}',
                'Updates',
                'Upgrade Craft CMS',
                'Version {version}',
                'Version',
                'Website',
                '{renewalPrice}/year per site for updates after that.',
            ]);
        }
    }
}
