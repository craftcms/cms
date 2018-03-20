<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * Class UpgradeInfo model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UpgradeInfo extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var array|null Available editions
     */
    public $editions;

    /**
     * @var string|null The Stripe publishable key
     */
    public $stripePublicKey;

    /**
     * @var array|null Known countries
     */
    public $countries;

    /**
     * @var array|null Known states
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
            [['editions', 'stripePublicKey', 'countries', 'states'], 'required'],
        ];
    }
}
