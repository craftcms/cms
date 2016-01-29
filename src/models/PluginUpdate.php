<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Stores the available plugin update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PluginUpdate extends Model
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
     * @var boolean Status
     */
    public $status = false;

    /**
     * @var string Display name
     */
    public $displayName;

    /**
     * @var boolean Critical update available
     */
    public $criticalUpdateAvailable = false;

    /**
     * @var PluginNewRelease[] Releases
     */
    public $releases;

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
            [['latestDate'], 'craft\\app\\validators\\DateTime'],
            [
                [
                    'class',
                    'localVersion',
                    'latestVersion',
                    'latestDate',
                    'status',
                    'displayName',
                    'criticalUpdateAvailable',
                    'releases'
                ],
                'safe',
                'on' => 'search'
            ],
        ];
    }
}
