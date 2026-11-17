<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Enums;

use JsonSerializable;

use function CraftCms\Cms\t;

/**
 * Job status values for queue status & progress tracking.
 */
enum JobStatus: int implements JsonSerializable
{
    case Pending = 1;
    case Reserved = 2;
    case Done = 3;
    case Failed = 4;
    case Delayed = 5;
    case Cancelled = 6;

    public function label(): string
    {
        return match ($this) {
            self::Pending => t('Pending'),
            self::Reserved => t('Reserved'),
            self::Done => t('Done'),
            self::Failed => t('Failed'),
            self::Delayed => t('Delayed'),
            self::Cancelled => t('Canceled'),
        };
    }

    /**
     * Whether a job can move from this status to the given one.
     *
     * Jobs only become pending or delayed when they're queued or retried, which starts them over
     * from any status, so neither is a transition target.
     */
    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Pending, self::Delayed, self::Reserved => in_array($status, [self::Reserved, self::Done, self::Failed, self::Cancelled], true),
            self::Failed => $status === self::Cancelled,
            self::Done, self::Cancelled => false,
        };
    }

    public static function tryFromName(?string $name = null): ?self
    {
        if (! $name) {
            return null;
        }

        foreach (self::cases() as $case) {
            if (strcasecmp($case->name, $name) === 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @return array{label: string, value: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'label' => $this->label(),
            'value' => $this->value,
        ];
    }
}
