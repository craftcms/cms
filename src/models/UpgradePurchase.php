<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * Used to hold edition upgrade purchase order data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UpgradePurchase extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string|null CC token ID
     */
    public $ccTokenId;

    /**
     * @var int|null CC expiry month
     */
    public $expMonth;

    /**
     * @var int|null CC expiry year
     */
    public $expYear;

    /**
     * @var int|null Edition
     */
    public $edition;

    /**
     * @var int|null Expected price
     */
    public $expectedPrice;

    /**
     * @var string|null Customer name
     */
    public $name;

    /**
     * @var string|null Customer email
     */
    public $email;

    /**
     * @var string|null Business name
     */
    public $businessName;

    /**
     * @var string|null Business address 1
     */
    public $businessAddress1;

    /**
     * @var string|null Business address 2
     */
    public $businessAddress2;

    /**
     * @var string|null Business city
     */
    public $businessCity;

    /**
     * @var string|null Business state
     */
    public $businessState;

    /**
     * @var string|null Business country
     */
    public $businessCountry;

    /**
     * @var string|null Business zip
     */
    public $businessZip;

    /**
     * @var string|null Business tax ID
     */
    public $businessTaxId;

    /**
     * @var string|null Purchase notes
     */
    public $purchaseNotes;

    /**
     * @var string|null Coupon code
     */
    public $couponCode;

    /**
     * @var bool Success
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
            [['edition'], 'number', 'integerOnly' => true],
            [['edition', 'expectedPrice', 'name', 'email'], 'required'],
        ];

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($this->expectedPrice != 0) {
            // CC info is also required
            $rules[] = [['ccTokenId', 'expMonth', 'expYear'], 'required'];
        }

        return $rules;
    }
}
