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
                '(included)',
                '({period} days)',
                'Abandoned',
                'Active Installs',
                'Active Trials',
                'Active trials added to the cart.',
                'Activity',
                'Add all to cart',
                'Add to cart',
                'Added to cart',
                'Already in your cart',
                'Ascending',
                'Auto-renew for {price} annually, starting on {date}.',
                'Buy now',
                'Cart',
                'Categories',
                'Changelog',
                'Checkout',
                'Closed Issues',
                'Compatibility',
                'Continue shopping',
                'Couldn’t add all items to the cart.',
                'Couldn’t change Craft CMS edition.',
                'Couldn’t load CMS editions.',
                'Couldn’t load active trials.',
                'Couldn’t update cart’s email.',
                'Couldn’t update item in cart.',
                'Craft CMS edition changed.',
                'Critical',
                'Descending',
                'Discover',
                'Documentation',
                'Editions' => 'Editions',
                'For when you’re building a website for yourself or a friend.',
                'For when you’re building something professionally for a client or team.',
                'Free',
                'Includes one year of updates.',
                'Install with Composer',
                'Install',
                'Installation Instructions',
                'Installed as a trial',
                'Installed',
                'Item',
                'Items in your cart',
                'Last Update',
                'Last release',
                'License',
                'Licensed',
                'Loading Plugin Store…',
                'Merged PRs',
                'Name',
                'New Issues',
                'No results.',
                'Only up to {version} is compatible with your version of Craft.',
                'Open PRs',
                'Overview',
                'Page not found.',
                'Plugin Store',
                'Plugin edition changed.',
                'Popularity',
                'Pricing',
                'Reactivate',
                'Remove',
                'Report plugin',
                'Repository',
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
                'To install this plugin with composer, copy the command above to your terminal.',
                'Total Price',
                'Total releases',
                'Try for free',
                'Try',
                'Updates until {date}',
                'Updates',
                'Upgrade Craft CMS',
                'Version {version}',
                'Version',
                'Website',
                'Your cart is empty.',
                '{num, number} {num, plural, =1{year} other{years}} of updates',
                '{renewalPrice}/year per site for updates after that.',
            ]);
        }
    }
}
