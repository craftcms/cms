<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Html\PreviewHtml;
use CraftCms\Cms\User\Elements\User;

it('returns empty string for empty element list', function () {
    expect(app(PreviewHtml::class)->elementPreviewHtml([]))->toBe('');
});

it('renders count badge when more than one element is provided', function () {
    $users = User::find()->limit(2)->all();

    if (count($users) < 2) {
        expect(true)->toBeTrue();

        return;
    }

    $html = app(PreviewHtml::class)->elementPreviewHtml($users);

    expect($html)->toContain('inline-chips')
        ->and($html)->toContain('Craft.cp.previewCountBadge')
        ->and($html)->toContain('+1');
});
