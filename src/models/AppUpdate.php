<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\validators\DateTimeValidator;

/**
 * Stores the available Craft update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AppUpdate extends Model
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function populateModel($model, $config)
    {
        if (isset($config['releases'])) {
            foreach ($config['releases'] as $key => $value) {
                if (!$value instanceof AppNewRelease) {
                    $config['releases'][$key] = AppNewRelease::create($value);
                }
            }
        }

        parent::populateModel($model, $config);
    }

    // Properties
    // =========================================================================

    /**
     * @var string Local build
     */
    public $localBuild;

    /**
     * @var string Local version
     */
    public $localVersion;

    /**
     * @var string Latest version
     */
    public $latestVersion;

    /**
     * @var string Latest build
     */
    public $latestBuild;

    /**
     * @var \DateTime Latest date
     */
    public $latestDate;

    /**
     * @var string Target version
     */
    public $targetVersion;

    /**
     * @var string Target build
     */
    public $targetBuild;

    /**
     * @var string Real latest version
     */
    public $realLatestVersion;

    /**
     * @var string Real latest build
     */
    public $realLatestBuild;

    /**
     * @var \DateTime Real latest date
     */
    public $realLatestDate;

    /**
     * @var boolean Critical update available
     */
    public $criticalUpdateAvailable = false;

    /**
     * @var boolean Manual update required
     */
    public $manualUpdateRequired = false;

    /**
     * @var boolean Breakpoint release
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
            [
                [
                    'localBuild',
                    'localVersion',
                    'latestVersion',
                    'latestBuild',
                    'latestDate',
                    'targetVersion',
                    'targetBuild',
                    'realLatestVersion',
                    'realLatestBuild',
                    'realLatestDate',
                    'criticalUpdateAvailable',
                    'manualUpdateRequired',
                    'breakpointRelease',
                    'licenseUpdated',
                    'versionUpdateStatus',
                    'manualDownloadEndpoint',
                    'releases'
                ],
                'safe',
                'on' => 'search'
            ],
        ];
    }
}
