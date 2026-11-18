<?php

declare(strict_types=1);

use craft\elements\User as LegacyUser;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->layout = FieldLayoutModel::factory()->create(['type' => User::class]);
    Fields::refreshFields();
});

it('finds the saved field layout by a legacy element class name', function() {
    expect(Craft::$app->getFields()->getLayoutByType(LegacyUser::class)?->id)->toBe($this->layout->id);
});

it('finds all field layouts by a legacy element class name', function() {
    expect(array_map(fn($layout) => $layout->id, Craft::$app->getFields()->getLayoutsByType(LegacyUser::class)))
        ->toBe([$this->layout->id]);
});

it('deletes field layouts by a legacy element class name', function() {
    expect(Craft::$app->getFields()->deleteLayoutsByType(LegacyUser::class))->toBeTrue()
        ->and(Fields::getLayoutsByType(User::class))->toBeEmpty();
});
