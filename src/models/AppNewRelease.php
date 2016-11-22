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
 * Stores the info for a Craft release.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AppNewRelease extends Model
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
     * @var string Notes
     */
    public $notes;

    /**
     * @var string Type
     */
    public $type;

    /**
     * @var boolean Critical
     */
    public $critical = false;

    /**
     * @var boolean Manual
     */
    public $manual = false;

    /**
     * @var boolean Breakpoint
     */
    public $breakpoint = false;

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
        ];
    }
}
