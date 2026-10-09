<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Nodes\Table;

it('exposes a delete modal URL alongside the delete endpoint', function () {
    $props = Table::make('locations')
        ->deletable('locations/delete', modalUrl: 'locations/delete-modal')
        ->props();

    expect($props['deleteUrl'])->toBe('locations/delete')
        ->and($props['deleteModalUrl'])->toBe('locations/delete-modal')
        ->and($props['bulkDeletable'])->toBeFalse();
});

it('has no delete modal URL by default', function () {
    expect(Table::make('locations')->deletable('locations/delete')->props()['deleteModalUrl'])->toBeNull();
});
