<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Used to hold edition upgrade purchase order data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UpgradePurchase extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string CC token ID
     */
    public $ccTokenId;

    /**
     * @var integer CC expiry month
     */
    public $expMonth;

    /**
     * @var integer CC expiry year
     */
    public $expYear;

    /**
     * @var integer Edition
     */
    public $edition;

    /**
     * @var integer Expected price
     */
    public $expectedPrice;

    /**
     * @var string Customer name
     */
    public $name;

    /**
     * @var string Customer email
     */
    public $email;

    /**
     * @var string Business name
     */
    public $businessName;

    /**
     * @var string Business address 1
     */
    public $businessAddress1;

    /**
     * @var string Business address 2
     */
    public $businessAddress2;

    /**
     * @var string Business city
     */
    public $businessCity;

    /**
     * @var string Business state
     */
    public $businessState;

    /**
     * @var string Business country
     */
    public $businessCountry;

    /**
     * @var string Business zip
     */
    public $businessZip;

    /**
     * @var string Business tax ID
     */
    public $businessTaxId;

    /**
     * @var string Purchase notes
     */
    public $purchaseNotes;

    /**
     * @var string Coupon code
     */
    public $couponCode;

    /**
     * @var boolean Success
     */
    public $success = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [
                ['edition'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [['edition', 'expectedPrice', 'name', 'email'], 'required'],
            [
                ['ccTokenId', 'edition', 'expectedPrice', 'success'],
                'safe',
                'on' => 'search'
            ],
        ];

        if ($this->expectedPrice != 0) {
            // CC info is also required
            $rules[] = [['ccTokenId', 'expMonth', 'expYear'], 'required'];
        }

        return $rules;
    }
}
