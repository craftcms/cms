<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\events\UpdateReleaseEvent;
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
     * @event UpdateReleaseEvent The event that is triggered when determining if this release should be flagged as critical.
     * @see isCritical()
     * @since 5.7.0
     */
    public const EVENT_IS_CRITICAL = 'isCritical';

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

    /**
     * Returns whether the release should be flagged as critical.
     *
     * @return bool
     * @since 5.7.0
     */
    public function isCritical(Update $update): bool
    {
        if (!$this->critical) {
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_IS_CRITICAL)) {
            $event = new UpdateReleaseEvent([
                'update' => $update,
            ]);
            $this->trigger(self::EVENT_IS_CRITICAL, $event);
            if ($event->handled) {
                return false;
            }
        }

        return true;
    }
}
