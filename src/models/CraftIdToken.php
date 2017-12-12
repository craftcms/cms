<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use DateTime;

/**
 * Class CraftIdToken model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CraftIdToken extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null
     */
    public $id;

    /**
     * @var int|null
     */
    public $userId;

    /**
     * @var string|null
     */
    public $accessToken;

    /**
     * @var string|null
     */
    public $refreshToken;

    /**
     * @var DateTime|null
     */
    public $expiryDate;

    /**
     * @var DateTime|null
     */
    public $dateCreated;

    /**
     * @var DateTime|null
     */
    public $dateUpdated;

    /**
     * @var string
     */
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * Has token expired.
     *
     * @return bool
     */
    public function hasExpired()
    {
        $now = new DateTime();
        $expiryDate = $this->expiryDate;

        if ($now->getTimestamp() > $expiryDate->getTimestamp()) {
            return true;
        }

        return false;
    }

    /**
     * Remaining seconds before token expiry.
     *
     * @return int
     */
    public function getRemainingSeconds()
    {
        $now = new DateTime();
        $expiryDate = $this->expiryDate;

        return $expiryDate->getTimestamp() - $now->getTimestamp();
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();

        $attributes[] = 'expiryDate';

        return $attributes;
    }
}
