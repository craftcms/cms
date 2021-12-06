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
                'Address Line 1',
                'Address Line 2',
                'Already in your cart',
                'Ascending',
                'Back',
                'Billing',
                'Business Name',
                'Business Tax ID',
                'Buy later',
                'Buy now for {price}',
                'Buy now',
                'CVC',
                'Card number',
                'Cart',
                'Categories',
                'Changelog',
                'Checkout',
                'City',
                'Cloud Storage Integration',
                'Community Support (Slack, Stack Exchange)',
                'Compatibility',
                'Connect to your Craft ID',
                'Contact',
                'Content Modeling',
                'Continue as guest',
                'Continue',
                'Copy the package’s name for this plugin.',
                'Couldn’t add all items to the cart.',
                'Couldn’t load CMS editions.',
                'Couldn’t load active trials.',
                'Coupon Code',
                'Critical',
                'Descending',
                'Description',
                'Developer Support',
                'Documentation',
                'Features',
                'First Name',
                'For when you’re building a website for yourself or a friend.',
                'For when you’re building something professionally for a client or team.',
                'Free',
                'Go to Dashboard',
                'Information',
                'Install',
                'Installed as a trial',
                'Installed',
                'Item',
                'Items in your cart',
                'Last Name',
                'Last Update',
                'Last update',
                'Less',
                'License',
                'Licensed',
                'Loading Plugin Store…',
                'MM / YY',
                'Manage plugins',
                'More',
                'Multi-site Multi-lingual',
                'Name',
                'No results.',
                'Only up to {version} is compatible with your version of Craft.',
                'Package Name',
                'Page not found.',
                'Pay',
                'Payment Method',
                'Payment',
                'Plugin Store',
                'Popularity',
                'Price includes 1 year of updates.',
                'Pro Rate Discount',
                'Reactivate',
                'Remove',
                'Renewal price',
                'Report an issue',
                'Save as my new credit card',
                'Screenshots',
                'Search plugins',
                'Security & Bug Fixes',
                'See all',
                'Showing results for “{searchQuery}”',
                'Staff Picks',
                'Subtotal',
                'Support',
                'System Branding',
                'Thank You!',
                'The Plugin Store is not available, please try again later.',
                'The developer recommends using <a href="{url}">{name}</a> instead.',
                'There was a problem processing your credit card.',
                'This license is tied to another Craft install. Purchase a license for this install.',
                'This license is tied to another Craft install. Visit {accountLink} to detach it, or buy a new license.',
                'This plugin is no longer maintained.',
                'This plugin isn’t compatible with your version of Craft.',
                'This plugin requires PHP {v1}, but your composer.json file is currently set to {v2}.',
                'This plugin requires PHP {v1}, but your environment is currently running {v2}.',
                'Total Price',
                'Total',
                'Try for free',
                'Try',
                'Try',
                'Updates until {date} ({sign}{price})',
                'Updates until {date}',
                'Updates',
                'Upgrade Craft CMS',
                'Use a new credit card',
                'Use card {cardDetails}',
                'Use your Craft ID',
                'User Accounts',
                'Version {version}',
                'Version',
                'Website',
                'Your are currently using the {currentEdition} edition, and your licensed edition is {licensedEdition}.',
                'Your license key is invalid.',
                'Your order has been processed successfully.',
                'Zip Code',
                'stripe_error_approve_with_id',
                'stripe_error_authentication_required',
                'stripe_error_call_issuer',
                'stripe_error_card_not_supported',
                'stripe_error_card_velocity_exceeded',
                'stripe_error_currency_not_supported',
                'stripe_error_do_not_honor',
                'stripe_error_do_not_try_again',
                'stripe_error_duplicate_transaction',
                'stripe_error_expired_card',
                'stripe_error_fraudulent',
                'stripe_error_generic_decline',
                'stripe_error_incorrect_cvc',
                'stripe_error_incorrect_number',
                'stripe_error_incorrect_pin',
                'stripe_error_incorrect_zip',
                'stripe_error_insufficient_funds',
                'stripe_error_invalid_account',
                'stripe_error_invalid_amount',
                'stripe_error_invalid_cvc',
                'stripe_error_invalid_expiry_month',
                'stripe_error_invalid_expiry_year',
                'stripe_error_invalid_number',
                'stripe_error_invalid_pin',
                'stripe_error_issuer_not_available',
                'stripe_error_lost_card',
                'stripe_error_merchant_blacklist',
                'stripe_error_new_account_information_available',
                'stripe_error_no_action_taken',
                'stripe_error_not_permitted',
                'stripe_error_offline_pin_required',
                'stripe_error_online_or_offline_pin_required',
                'stripe_error_pickup_card',
                'stripe_error_pin_try_exceeded',
                'stripe_error_processing_error',
                'stripe_error_reenter_transaction',
                'stripe_error_restricted_card',
                'stripe_error_revocation_of_all_authorizations',
                'stripe_error_revocation_of_authorization',
                'stripe_error_security_violation',
                'stripe_error_service_not_allowed',
                'stripe_error_stolen_card',
                'stripe_error_stop_payment_order',
                'stripe_error_testmode_decline',
                'stripe_error_transaction_not_allowed',
                'stripe_error_try_again_later',
                'stripe_error_withdrawal_count_limit_exceeded',
                '{price} plus {renewalPrice}/year for updates',
                '{price}/year',
                '{renewalPrice}/year per site for updates after that.',
            ]);
        }
    }
}
