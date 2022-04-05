<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\helpers\DateTimeHelper;
use DateTimeZone;

/**
 * Stores the info for an update release.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UpdateRelease extends Model
{
    /**
     * @var string Version
     */
    public $version;

    /**
     * @var \DateTime|null Date
     */
    public $date;

    /**
     * @var bool Critical
     */
    public $critical = false;

    /**
     * @var string|null Notes
     */
    public $notes;

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
    public function fields()
    {
        $fields = parent::fields();

        // Don't include time zone in the date
        $fields['date'] = function(): ?string {
            if ($this->date !== null) {
                return DateTimeHelper::toDateTime($this->date)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s');
            }
            return null;
        };

        return $fields;
    }
}
