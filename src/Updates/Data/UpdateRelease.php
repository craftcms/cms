<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Data;

use CraftCms\Cms\Updates\Events\CriticalUpdateReleased;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 */
final readonly class UpdateRelease
{
    public function __construct(
        public string $version,
        public ?\DateTimeInterface $date = null,
        private bool $critical = false,
        public ?string $notes = null,
    ) {}

    public function isCritical(Update $update): bool
    {
        if (! $this->critical) {
            return false;
        }

        if (Event::hasListeners(CriticalUpdateReleased::class)) {
            Event::dispatch($event = new CriticalUpdateReleased($update));

            if ($event->isValid) {
                return false;
            }
        }

        return true;
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
