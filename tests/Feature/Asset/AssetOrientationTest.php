<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\OrientationConditionRule;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\ImageOrientation;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use Illuminate\Database\Query\Builder;

it('tells landscape, portrait, and square images apart, counting images within 2% as square', function (int $width, int $height, ImageOrientation $expected) {
    expect(ImageOrientation::fromDimensions($width, $height))->toBe($expected);
})->with([
    'landscape' => [200, 100, ImageOrientation::Landscape],
    'portrait' => [100, 200, ImageOrientation::Portrait],
    'square' => [100, 100, ImageOrientation::Square],
    'just wide enough to be square' => [100, 98, ImageOrientation::Square],
    'just too wide to be square' => [100, 97, ImageOrientation::Landscape],
    'just tall enough to be square' => [98, 100, ImageOrientation::Square],
    'just too tall to be square' => [97, 100, ImageOrientation::Portrait],
]);

describe('with images of each orientation', function () {
    beforeEach(function () {
        $image = fn (?int $width, ?int $height): int => AssetModel::factory()->createElement([
            'kind' => 'image',
            'filename' => uniqid('image-').'.png',
            'width' => $width,
            'height' => $height,
        ])->id;

        $this->landscape = $image(1000, 970);
        $this->portrait = $image(970, 1000);
        $this->square = $image(1000, 980);
        $this->unknown = $image(null, null);
        $this->ids = [$this->landscape, $this->portrait, $this->square, $this->unknown];
    });

    it('returns images’ orientations', function () {
        $orientation = fn (int $id): ?ImageOrientation => Asset::find()->id($id)->one()->getOrientation();

        expect($orientation($this->landscape))->toBe(ImageOrientation::Landscape)
            ->and($orientation($this->portrait))->toBe(ImageOrientation::Portrait)
            ->and($orientation($this->square))->toBe(ImageOrientation::Square)
            ->and($orientation($this->unknown))->toBeNull();
    });

    it('queries images by orientation, the same way it tells them apart', function (mixed $value, array $expected) {
        $ids = Asset::find()->id($this->ids)->orientation($value)->orderBy('id')->ids();

        expect($ids)->toBe(array_map(fn (string $name): int => $this->$name, $expected));
    })->with([
        'landscape' => ['landscape', ['landscape']],
        'enum' => [ImageOrientation::Portrait, ['portrait']],
        'square' => ['square', ['square']],
        'several' => [['portrait', 'square'], ['portrait', 'square']],
        'excluded' => [['not', 'square'], ['landscape', 'portrait']],
        'unknown' => ['diagonal', []],
    ]);

    it('filters images by orientation in conditions', function (string $operator, array $expected) {
        $rule = new AssetCondition(Asset::class)->createConditionRule(OrientationConditionRule::class);
        $rule->operator = $operator;
        $rule->values = ['landscape'];
        $query = Asset::find()->id($this->ids)->orderBy('id');
        $query->where(fn (Builder $builder) => $rule->modifyQuery($builder, $query));

        expect($query->ids())->toBe(array_map(fn (string $name): int => $this->$name, $expected))
            ->and($rule->matchElement(Asset::find()->id($this->landscape)->one()))->toBe($operator === 'in');
    })->with([
        'is one of' => ['in', ['landscape']],
        'is not one of' => ['ni', ['portrait', 'square']],
    ]);

    it('filters images by orientation in GraphQL, and says what it is', function () {
        gqlActivateFullAccessSchema();

        graphQL('{ assets(id: ['.implode(',', $this->ids).'], orientation: ["portrait"]) { id orientation } }')
            ->assertOk()
            ->assertJsonPath('data.assets', [['id' => (string) $this->portrait, 'orientation' => 'portrait']]);
    });
});
