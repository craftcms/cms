<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Enums\AssetIndexStatus;

it('defines every valid asset index transition', function () {
    $transitions = [
        AssetIndexStatus::Pending->value => [AssetIndexStatus::Processing],
        AssetIndexStatus::Processing->value => [
            AssetIndexStatus::Indexed,
            AssetIndexStatus::Skipped,
            AssetIndexStatus::Missing,
            AssetIndexStatus::Failed,
        ],
        AssetIndexStatus::Indexed->value => [],
        AssetIndexStatus::Skipped->value => [],
        AssetIndexStatus::Missing->value => [AssetIndexStatus::Pending],
        AssetIndexStatus::Failed->value => [AssetIndexStatus::Pending],
    ];

    foreach (AssetIndexStatus::cases() as $currentStatus) {
        foreach (AssetIndexStatus::cases() as $nextStatus) {
            expect($currentStatus->canTransitionTo($nextStatus))
                ->toBe(in_array($nextStatus, $transitions[$currentStatus->value], true));
        }
    }
});
