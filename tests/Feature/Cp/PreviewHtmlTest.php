<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Html\PreviewHtml;
use CraftCms\Cms\User\Models\User as UserModel;

it('returns empty string for empty element list', function () {
    expect(app(PreviewHtml::class)->elementPreviewHtml([]))->toBe('');
});

it('renders count badge when more than one element is provided', function () {
    $users = UserModel::factory()->count(2)->create()->map->asElement()->all();

    $html = app(PreviewHtml::class)->elementPreviewHtml($users);

    expect($html)->toContain('inline-chips')
        ->and($html)->toContain('Craft.cp.previewCountBadge')
        ->and($html)->toContain('+1');
});
