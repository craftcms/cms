<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use DateTime;

/**
 * Class CraftIdToken model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class CraftIdToken extends Model
{
    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var int|null
     */
    public ?int $userId = null;

    /**
     * @var string|null
     */
    public ?string $accessToken = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $expiryDate = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @var string|null
     */
    public ?string $uid = null;

    /**
     * Has token expired.
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        $now = new DateTime();
        $expiryDate = $this->expiryDate;

        return $now->getTimestamp() > $expiryDate->getTimestamp();
    }

    /**
     * Remaining seconds before token expiry.
     *
     * @return int
     */
    public function getRemainingSeconds(): int
    {
        $now = new DateTime();
        $expiryDate = $this->expiryDate;

        return $expiryDate->getTimestamp() - $now->getTimestamp();
    }
}
