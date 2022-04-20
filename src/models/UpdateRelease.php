<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\helpers\DateTimeHelper;
use DateTime;
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
    public string $version;

    /**
     * @var DateTime|null Date
     */
    public ?DateTime $date = null;

    /**
     * @var bool Critical
     */
    public bool $critical = false;

    /**
     * @var string|null Notes
     */
    public ?string $notes = null;

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();

        // Don't include time zone in the date
        $fields['date'] = function(): ?string {
            if (isset($this->date)) {
                return DateTimeHelper::toDateTime($this->date)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s');
            }
            return null;
        };

        return $fields;
    }
}
