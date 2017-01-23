<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use craft\base\Model;
use craft\validators\DateTimeValidator;

/**
 * Stores the available Craft update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AppUpdate extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string|null Local version
     */
    public $localVersion;

    /**
     * @var string|null Latest version
     */
    public $latestVersion;

    /**
     * @var \DateTime|null Latest date
     */
    public $latestDate;

    /**
     * @var string|null Target version
     */
    public $targetVersion;

    /**
     * @var string|null Real latest version
     */
    public $realLatestVersion;

    /**
     * @var \DateTime|null Real latest date
     */
    public $realLatestDate;

    /**
     * @var bool Critical update available
     */
    public $criticalUpdateAvailable = false;

    /**
     * @var bool Manual update required
     */
    public $manualUpdateRequired = false;

    /**
     * @var bool Breakpoint release
     */
    public $breakpointRelease = false;

    /**
     * @var string|null License updated
     */
    public $licenseUpdated;

    /**
     * @var string|null Version update status
     */
    public $versionUpdateStatus;

    /**
     * @var string|null Manual download endpoint
     */
    public $manualDownloadEndpoint;

    /**
     * @var AppUpdateRelease[] Releases
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

        if ($this->releases !== null) {
            foreach ($this->releases as $key => $value) {
                if (!$value instanceof AppUpdateRelease) {
                    $this->releases[$key] = new AppUpdateRelease($value);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'latestDate';
        $attributes[] = 'realLatestDate';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['latestDate', 'realLatestDate'], DateTimeValidator::class],
        ];
    }
}
