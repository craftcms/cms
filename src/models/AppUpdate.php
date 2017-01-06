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
     * @var string Local version
     */
    public $localVersion;

    /**
     * @var string Latest version
     */
    public $latestVersion;

    /**
     * @var \DateTime Latest date
     */
    public $latestDate;

    /**
     * @var string Target version
     */
    public $targetVersion;

    /**
     * @var string Real latest version
     */
    public $realLatestVersion;

    /**
     * @var \DateTime Real latest date
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
     * @var string License updated
     */
    public $licenseUpdated;

    /**
     * @var string Version update status
     */
    public $versionUpdateStatus;

    /**
     * @var string Manual download endpoint
     */
    public $manualDownloadEndpoint;

    /**
     * @var AppNewRelease[] Releases
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
                if (!$value instanceof AppNewRelease) {
                    $this->releases[$key] = new AppNewRelease($value);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes()
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
