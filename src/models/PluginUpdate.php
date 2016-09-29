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
 * Stores the available plugin update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PluginUpdate extends Model
{
    // Constants
    // =========================================================================

    const STATUS_UP_TO_DATE = 'UpToDate';
    const STATUS_UPDATE_AVAILABLE = 'UpdateAvailable';
    const STATUS_DELETED = 'Deleted';
    const STATUS_UNKNOWN = 'Unknown';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function populateModel($model, $config)
    {
        if (isset($config['releases'])) {
            foreach ($config['releases'] as $key => $value) {
                if (!$value instanceof PluginNewRelease) {
                    $config['releases'][$key] = PluginNewRelease::create($value);
                }
            }
        }

        parent::populateModel($model, $config);
    }

    // Properties
    // =========================================================================

    /**
     * @var string Class
     */
    public $class;

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
     * @var string Display name
     */
    public $displayName;

    /**
     * @var boolean Critical update available
     */
    public $criticalUpdateAvailable = false;

    /**
     * @var boolean Manual update required
     */
    public $manualUpdateRequired = false;

    /**
     * @var string Manual download endpoint
     */
    public $manualDownloadEndpoint;

    /**
     * @var PluginNewRelease[] Releases
     */
    public $releases;

    /**
     * @var string Status
     */
    public $status = self::STATUS_UNKNOWN;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes()
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'latestDate';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['latestDate'], DateTimeValidator::class],
            [
                ['status'],
                'in',
                'range' => [
                    self::STATUS_UP_TO_DATE,
                    self::STATUS_UPDATE_AVAILABLE,
                    self::STATUS_DELETED,
                    self::STATUS_UNKNOWN
                ]
            ]
        ];
    }
}
