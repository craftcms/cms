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
 * Stores the info for a plugin release.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PluginNewRelease extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string Version
     */
    public $version;

    /**
     * @var \DateTime Date
     */
    public $date;

    /**
     * @var \DateTime Date
     */
    public $localizedDate;

    /**
     * @var string Notes
     */
    public $notes;

    /**
     * @var boolean Critical
     */
    public $critical = false;

    /**
     * @var string Manual Download Endpoint
     */
    public $manualDownloadEndpoint;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes()
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'date';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date'], DateTimeValidator::class],
            [
                ['version', 'date', 'notes', 'critical'],
                'safe',
                'on' => 'search'
            ],
        ];
    }
}
