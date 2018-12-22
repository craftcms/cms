<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * Craft/plugin update model.
 *
 * @property bool $hasCritical Whether any of the updates have a critical release available
 * @property bool $hasReleases Whether there are any releases available
 * @property UpdateRelease|null $latest The latest release (if any are available)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Update extends Model
{
    // Constants
    // =========================================================================

    const STATUS_ELIGIBLE = 'eligible';
    const STATUS_BREAKPOINT = 'breakpoint';
    const STATUS_EXPIRED = 'expired';

    // Properties
    // =========================================================================

    /**
     * @var string The status of the update (eligible, breakpoint, or expired)
     */
    public $status = self::STATUS_ELIGIBLE;

    /**
     * @var float|null The price to renew the license, if expired
     */
    public $renewalPrice;

    /**
     * @var string|null The renewal price's currency
     */
    public $renewalCurrency;

    /**
     * @var string|null The URL that the Renew button should link to
     */
    public $renewalUrl;

    /**
     * @var UpdateRelease[] The available releases
     */
    public $releases = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        foreach ($this->releases as $key => $release) {
            if (!$release instanceof UpdateRelease) {
                $this->releases[$key] = new UpdateRelease($release);
            }
        }
    }

    /**
     * Returns whether there are any releases available.
     *
     * @return bool
     */
    public function getHasReleases(): bool
    {
        return !empty($this->releases);
    }

    /**
     * Returns whether there are any critical releases available.
     *
     * @return bool
     */
    public function getHasCritical(): bool
    {
        foreach ($this->releases as $release) {
            if ($release->critical) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the latest release (if any are available).
     *
     * @return UpdateRelease|null
     */
    public function getLatest()
    {
        return $this->releases[0] ?? null;
    }
}
