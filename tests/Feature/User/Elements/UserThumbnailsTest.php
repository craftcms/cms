<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\Asset\ControlPanelAssetTransformDriver;
use CraftCms\Cms\Tests\TestClasses\Field\ThumbnailField;
use CraftCms\Cms\User\Models\User;

beforeEach(function () {
    ThumbnailField::$requests = [];
    $this->driver = new ControlPanelAssetTransformDriver;
    $this->driver->register();
    Cms::config()->defaultAssetTransformer('test');
    $this->user = User::factory()->createElement();
    $this->user->setPhoto(Asset::factory()->createElement(['width' => 800, 'height' => 400]));
});

it('preserves native square photo crops at both resolutions with a non-crop mode', function () {
    expect($this->user->getThumbHtml(120, ImageTransformMode::Letterbox))->toContainTag('craft-thumbnail', ['rounded' => true, 'mode' => 'letterbox'])
        ->and(array_column($this->driver->requests, 'parameters'))->toBe([
            ['height' => 120, 'mode' => 'crop', 'width' => 120],
            ['height' => 240, 'mode' => 'crop', 'width' => 240],
        ]);
});

it('defaults user thumbnails to fit while retaining native photo crops', function () {
    expect($this->user->getThumbHtml(120))->toContainTag('craft-thumbnail', ['mode' => 'fit'])
        ->and($this->driver->requests[0]->parameters)->toBe(['height' => 120, 'mode' => 'crop', 'width' => 120]);
});

it('forwards the requested mode to configured fields before falling back to native photos', function (?string $value) {
    $field = Field::factory()->create(['handle' => 'thumbnail', 'type' => ThumbnailField::class]);
    Fields::refreshFields();
    $customField = new CustomField(config: ['fieldUid' => $field->uid, 'uid' => Str::uuid()->toString()]);
    $layout = $this->user->getFieldLayout();
    $layout->tab('Thumbnail', fn (FieldLayoutTab $tab) => $tab->add($customField));
    $layout->thumbFieldKey = "layoutElement:{$customField->uid}";
    expect(Fields::saveLayout($layout))->toBeTrue();
    $this->user->setFieldValue('thumbnail', $value);

    $html = $this->user->getThumbHtml(120, ImageTransformMode::Letterbox);

    expect(ThumbnailField::$requests)->toBe([[$value === '' ? null : $value, $this->user, 120, ImageTransformMode::Letterbox]]);
    if ($value) {
        expect($html)->toBe($value)
            ->and($this->driver->requests)->toBeEmpty();
    } else {
        expect($html)->toContainTag('craft-thumbnail', ['rounded' => true, 'mode' => 'letterbox'])
            ->and(array_column($this->driver->requests, 'parameters'))->toBe([
                ['height' => 120, 'mode' => 'crop', 'width' => 120],
                ['height' => 240, 'mode' => 'crop', 'width' => 240],
            ]);
    }
})->with([
    'configured HTML' => '<b>Configured thumbnail</b>',
    'normalized empty string fallback' => '',
    'null fallback' => [null],
]);
