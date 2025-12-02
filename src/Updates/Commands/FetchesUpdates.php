<?php

declare(strict_types=1);

namespace CraftCms\Cms\Updates\Commands;

use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Updates\Data\Update;
use CraftCms\Cms\Updates\Data\Updates as UpdatesData;
use CraftCms\Cms\Updates\Enums\UpdateStatus;

use function Laravel\Prompts\spin;

/**
 * @internal
 */
trait FetchesUpdates
{
    protected function fetchUpdates(array $constraints = []): UpdatesData
    {
        $updates = null;

        spin(function () use ($constraints, &$updates) {
            $updateData = resolve(Api::class)->getUpdates($constraints);
            $updates = UpdatesData::fromArray($updateData);
        }, 'Fetching available updates');

        return $updates;
    }

    protected function formatLine(string $handle, string $from, Update $update): array
    {
        $expired = $update->status === UpdateStatus::EXPIRED;
        $color = $expired
            ? fn ($value) => $this->gray($this->reset($value))
            : fn ($value) => $value;

        return [
            'handle' => $color($this->cyan($handle)),
            'from' => $color($this->cyan($from)),
            'to' => $color($this->cyan($update->latest()?->version)),
            'status' => match (true) {
                $update->hasCritical() => $this->bold($color($this->red('CRITICAL'))),
                $expired => $this->bold($color($this->red('EXPIRED'))),
                default => '',
            }.(
                $update->phpConstraint && ($phpConstraintError = PHP::checkConstraint($update->phpConstraint))
                    ? $color($this->red(" ⚠️ $phpConstraintError"))
                    : ''
            ),
        ];
    }
}
