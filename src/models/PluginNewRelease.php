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
     * @var bool Critical
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
    public function datetimeAttributes(): array
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
        ];
    }
}
