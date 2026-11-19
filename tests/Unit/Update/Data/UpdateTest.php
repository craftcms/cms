<?php

declare(strict_types=1);

use CraftCms\Cms\Update\Data\Update;
use CraftCms\Cms\Update\Enums\UpdateStatus;

test('falls back to eligible when the status is missing or unknown', function (array $data) {
    expect(Update::fromArray($data)->status)->toBe(UpdateStatus::ELIGIBLE);
})->with([
    'missing' => [[]],
    'unknown' => [['status' => 'paused']],
]);

test('keeps a known status', function () {
    expect(Update::fromArray(['status' => 'expired'])->status)->toBe(UpdateStatus::EXPIRED);
});
