<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Html\PreviewHtml;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\User\Models\User as UserModel;

it('returns empty string for empty element list', function () {
    expect(app(PreviewHtml::class)->elementPreviewHtml([]))->toBe('');
});

it('renders count badge when more than one element is provided', function () {
    $users = UserModel::factory()->count(2)->create()->map->asElement()->all();

    $html = app(PreviewHtml::class)->elementPreviewHtml($users);

    expect($html)->toContain('inline-chips')
        ->and($html)->toContainTag('craft-button', ['type' => 'button', 'variant' => 'plain', 'size' => 'small'])
        ->and($html)->toContain('Craft.cp.previewCountBadge')
        ->and($html)->toContain('data-other=')
        ->and($html)->toContain('+1');
});

it('renders a plain count button for more than one component', function () {
    $entryTypes = collect(EntryTypeModel::factory()->count(2)->create())
        ->map(fn (EntryTypeModel $model) => app(EntryTypes::class)->getEntryTypeById($model->id))
        ->all();

    $html = app(PreviewHtml::class)->componentPreviewHtml($entryTypes);

    expect($html)->toContainTag('craft-button', ['type' => 'button', 'variant' => 'plain', 'size' => 'small'])
        ->and($html)->toContain('data-other=')
        ->and($html)->not->toContain(' label="');
});
