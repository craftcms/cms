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
                'All reviews',
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
                'Date Created',
                'Descending',
                'Developer Response',
                'Direction',
                'Discover',
                'Documentation',
                'Edited {updated}',
                'Editions' => 'Editions',
                'Everything in {edition}, plus…',
                'Failed to load plugin reviews. Please try again',
                'For everything else.',
                'For marketing sites managed by small teams.',
                'For personal sites built for yourself or a friend.',
                'Free',
                'Install with Composer',
                'Install',
                'Installation Instructions',
                'Installed as a trial',
                'Installed',
                'Item',
                'Items in your cart',
                'Last Update',
                'Last release',
                'Leave a review',
                'License',
                'Licensed',
                'Loading Plugin Store…',
                'Merged PRs',
                'Name',
                'New Issues',
                'No results.',
                'Only up to {version} is compatible with your version of Craft.',
                'Open PRs',
                'Order by',
                'Overview',
                'Page not found.',
                'Plugin Store',
                'Plugin edition changed.',
                'Plus {renewalPrice}/year for updates after one year.',
                'Popularity',
                'Pricing',
                'Rating',
                'Rating: {rating} out of {max} stars',
                'Reactivate',
                'Remove',
                'Report plugin',
                'Repository',
                'Reviews',
                'Search plugins',
                'See all',
                'Showing results for “{searchQuery}”',
                'The Plugin Store is not available, please try again later.',
                'The developer recommends using <a href="{url}">{name}</a> instead.',
                'This license is tied to another Craft install. Visit {accountLink} to detach it, or buy a new license.',
                'This plugin doesn’t have any reviews with comments.',
                'This plugin doesn’t have any reviews.',
                'This plugin is no longer maintained.',
                'This plugin isn’t compatible with your version of Craft.',
                'This plugin requires Craft CMS {name} edition.',
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
                '{totalReviews, plural, =1{# Review} other{# Reviews}}',
            ]);
        }
    }
}
