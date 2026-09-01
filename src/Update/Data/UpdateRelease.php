<?php

declare(strict_types=1);

namespace CraftCms\Cms\Update\Data;

use CraftCms\Cms\Update\Events\CriticalUpdateReleased;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;

/**
 * @internal
 */
readonly class UpdateRelease
{
    public function __construct(
        public string $version,
        public ?DateTimeInterface $date = null,
        private bool $critical = false,
        public ?string $notes = null,
    ) {}

    public function isCritical(Update $update): bool
    {
        if (! $this->critical) {
            return false;
        }

        event($event = new CriticalUpdateReleased($update));

        return $event->isValid;
    }

    /** @param array{version: string, date?: string|null, critical?: bool, notes?: string|null} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            version: $data['version'],
            date: isset($data['date']) ? Date::parse($data['date']) : null,
            critical: $data['critical'] ?? false,
            notes: $data['notes'] ?? null,
        );
    }
}
