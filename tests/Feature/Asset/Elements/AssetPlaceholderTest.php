<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\PreviewHandlers\Image;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Tests\TestClasses\Asset\ControlPanelAssetTransformDriver;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @param  list<list<string>>  $grid
 */
function placeholderAsset(array $grid, string $kind = FileKind::Image->value): Asset
{
    $asset = new Asset;
    $asset->kind = $kind;
    $asset->colors = new ImageColors(dominant: '#3a6ea5', grid: $grid);

    return $asset;
}

function placeholderImage(Asset $asset): GdImage
{
    return imagecreatefromstring(base64_decode(substr($asset->getPlaceholderDataUrl(), strlen('data:image/png;base64,'))));
}

it('scales the color grid up three times', function () {
    $image = placeholderImage(placeholderAsset(array_fill(0, 2, array_fill(0, 3, '#3a6ea5'))));
    $colors = [];

    for ($y = 0; $y < imagesy($image); $y++) {
        for ($x = 0; $x < imagesx($image); $x++) {
            $colors[] = sprintf('#%06x', imagecolorat($image, $x, $y) & 0xFFFFFF);
        }
    }

    expect([imagesx($image), imagesy($image)])->toBe([9, 6])
        ->and(array_unique($colors))->toBe(['#3a6ea5']);
});

it('blends smoothly between regions', function () {
    $image = placeholderImage(placeholderAsset([['#000000', '#ffffff']]));
    $row = array_map(fn (int $x): int => imagecolorat($image, $x, 1) & 0xFF, range(0, imagesx($image) - 1));

    expect($row)->toHaveCount(6)
        ->and($row)->toBe(array_values(Arr::sort($row)))
        ->and([$row[0], array_last($row)])->toBe([0, 255])
        ->and(array_filter($row, fn (int $value): bool => $value > 0 && $value < 255))->toHaveCount(2);
});

it('keeps transparent regions transparent', function () {
    $image = placeholderImage(placeholderAsset([['#3a6ea5', '#3a6ea500']]));
    $alpha = fn (int $x): int => (imagecolorat($image, $x, 1) >> 24) & 0x7F;

    expect($alpha(0))->toBe(0)
        ->and($alpha(imagesx($image) - 1))->toBe(127);
});

it('encodes a BlurHash with one component per region', function (array $grid, string $sizeFlag) {
    $hash = placeholderAsset($grid)->getBlurhash();

    expect($hash)->toHaveLength(28)
        ->and($hash[0])->toBe($sizeFlag);
})->with([
    'landscape' => [array_fill(0, 3, array_fill(0, 4, '#3a6ea5')), 'L'],
    'portrait' => [array_fill(0, 4, array_fill(0, 3, '#3a6ea5')), 'T'],
]);

it('is null without a color grid', function (?ImageColors $colors) {
    $asset = new Asset;
    $asset->kind = FileKind::Image->value;
    $asset->colors = $colors;

    expect($asset->getPlaceholderDataUrl())->toBeNull()
        ->and($asset->getBlurhash())->toBeNull();
})->with([
    'not sampled yet' => [null],
    'inconclusive' => [new ImageColors],
]);

it('is null for files that aren’t images', function () {
    $asset = placeholderAsset([['#3a6ea5']], FileKind::Pdf->value);

    expect($asset->getPlaceholderDataUrl())->toBeNull()
        ->and($asset->getBlurhash())->toBeNull();
});

describe('as a background', function () {
    beforeEach(function () {
        config()->set('filesystems.disks.test-disk', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/asset-placeholder-test/test-disk'),
        ]);
        new ControlPanelAssetTransformDriver()->register();
        Cms::config()->defaultAssetTransformer('test');
        $this->asset = AssetModel::factory()->createElement([
            'volumeId' => Volume::factory()->create(['fs' => 'test-disk'])->id,
            'kind' => FileKind::Image->value,
        ]);
    });

    it('paints the placeholder behind <img> tags', function () {
        $this->asset->colors = new ImageColors(grid: [['#3a6ea5']]);

        expect(new Crawler((string) $this->asset->getImg(['width' => 100, 'height' => 100]))->filter('img')->attr('style'))
            ->toBe("background: url({$this->asset->getPlaceholderDataUrl()}) center / cover no-repeat;");
    });

    it('leaves the placeholder off <img> tags for images with transparent regions', function (?ImageColors $colors) {
        $this->asset->colors = $colors;

        expect(new Crawler((string) $this->asset->getImg(['width' => 100, 'height' => 100]))->filter('img')->attr('style'))->toBeNull();
    })->with([
        'transparent' => [new ImageColors(grid: [['#3a6ea5', '#3a6ea500']])],
        'not sampled yet' => [null],
    ]);

    it('paints the placeholder behind the file preview until it loads', function () {
        $this->asset->colors = new ImageColors(grid: [['#3a6ea5']]);

        $img = new Crawler(new Image($this->asset)->getPreviewHtml())->filter('img');

        expect($img->attr('style'))->toBe("background: url({$this->asset->getPlaceholderDataUrl()}) center / 100% 100% no-repeat;")
            ->and($img->attr('onload'))->toStartWith("this.style.background = '';");
    });
});
