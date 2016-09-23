<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Class UpgradeInfo model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UpgradeInfo extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var array Available editions
     */
    public $editions;

    /**
     * @var string The Stripe publishable key
     */
    public $stripePublicKey;

    /**
     * @var array Known countries
     */
    public $countries;

    /**
     * @var array Known states
     */
    public $states;

    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['editions', 'stripePublicKey', 'countries', 'states'],
                'required'
            ],
        ];
    }
}
