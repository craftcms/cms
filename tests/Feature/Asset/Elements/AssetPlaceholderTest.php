<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\PreviewHandlers\Image;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageColors;
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

it('draws one pixel per region of the color grid', function () {
    $url = placeholderAsset([
        ['#ff0000', '#00ff00', '#0000ff'],
        ['#ffffff', '#000000', '#3a6ea5'],
    ])->getPlaceholderDataUrl();

    expect($url)->toStartWith('data:image/png;base64,');

    $image = imagecreatefromstring(base64_decode(substr($url, strlen('data:image/png;base64,'))));
    $pixel = fn (int $x, int $y): string => sprintf('#%06x', imagecolorat($image, $x, $y) & 0xFFFFFF);

    expect([imagesx($image), imagesy($image)])->toBe([3, 2])
        ->and([
            [$pixel(0, 0), $pixel(1, 0), $pixel(2, 0)],
            [$pixel(0, 1), $pixel(1, 1), $pixel(2, 1)],
        ])->toBe([
            ['#ff0000', '#00ff00', '#0000ff'],
            ['#ffffff', '#000000', '#3a6ea5'],
        ]);
});

it('keeps transparent regions transparent', function () {
    $url = placeholderAsset([['#3a6ea5', '#3a6ea500']])->getPlaceholderDataUrl();

    $image = imagecreatefromstring(base64_decode(substr($url, strlen('data:image/png;base64,'))));
    $alpha = fn (int $x): int => (imagecolorat($image, $x, 0) >> 24) & 0x7F;

    expect($alpha(0))->toBe(0)
        ->and($alpha(1))->toBe(127);
});

it('is null without a color grid', function (?ImageColors $colors) {
    $asset = new Asset;
    $asset->kind = FileKind::Image->value;
    $asset->colors = $colors;

    expect($asset->getPlaceholderDataUrl())->toBeNull();
})->with([
    'not sampled yet' => [null],
    'inconclusive' => [new ImageColors],
]);

it('is null for files that aren’t images', function () {
    expect(placeholderAsset([['#3a6ea5']], FileKind::Pdf->value)->getPlaceholderDataUrl())->toBeNull();
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
