<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Data;

use CraftCms\Cms\Updates\Events\CriticalUpdateReleased;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;

/**
 * @internal
 */
final readonly class UpdateRelease
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
