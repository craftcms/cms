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
 * @since 3.0.0
 */
class Update extends Model
{
    public const STATUS_ELIGIBLE = 'eligible';
    public const STATUS_BREAKPOINT = 'breakpoint';
    public const STATUS_EXPIRED = 'expired';

    /**
     * @var string The status of the update (eligible, breakpoint, or expired)
     * @phpstan-var self::STATUS_ELIGIBLE|self::STATUS_BREAKPOINT|self::STATUS_EXPIRED
     */
    public string $status = self::STATUS_ELIGIBLE;

    /**
     * @var float|null The price to renew the license, if expired
     */
    public ?float $renewalPrice = null;

    /**
     * @var string|null The renewal price's currency
     */
    public ?string $renewalCurrency = null;

    /**
     * @var string|null The URL that the Renew button should link to
     */
    public ?string $renewalUrl = null;

    /**
     * @var UpdateRelease[] The available releases
     */
    public array $releases = [];

    /**
     * @var string|null The PHP version constraint required by this version
     * @since 3.5.15
     */
    public ?string $phpConstraint = null;

    /**
     * @var string The package name that should be used when updating
     */
    public string $packageName;

    /**
     * @var bool Whether the package is abandoned
     * @since 3.6.7
     */
    public bool $abandoned = false;

    /**
     * @var string|null The name of the suggested replacement package
     * @since 3.6.7
     */
    public ?string $replacementName = null;

    /**
     * @var string|null The handle of the suggested replacement package
     * @since 3.6.7
     */
    public ?string $replacementHandle = null;

    /**
     * @var string|null The URL of the suggested replacement package
     * @since 3.6.7
     */
    public ?string $replacementUrl = null;

    /**
     * @inheritdoc
     */
    public function init(): void
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
    public function getLatest(): ?UpdateRelease
    {
        return $this->releases[0] ?? null;
    }
}
