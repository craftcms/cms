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
 * Stores the info for an update release.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UpdateRelease extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string|null Version
     */
    public $version;

    /**
     * @var \DateTime|null Date
     */
    public $date;

    /**
     * @var string|null Notes
     */
    public $notes;

    /**
     * @var bool Critical
     */
    public $critical = false;

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
