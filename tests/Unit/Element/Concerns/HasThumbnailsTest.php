<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use Symfony\Component\DomCrawler\Crawler;

class TestThumbnailElement extends Element
{
    public array $thumbRequests = [];

    public ?FieldLayout $thumbnailLayout = null;

    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        return $this->thumbnailLayout;
    }

    protected ?string $customThumbUrl = null;

    protected ?string $customThumbSvg = null;

    protected ?string $customThumbAlt = null;

    protected bool $checkeredThumb = false;

    protected bool $roundedThumb = false;

    protected bool $animatedThumb = false;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    public function setCustomThumbUrl(?string $url): void
    {
        $this->customThumbUrl = $url;
    }

    public function setCustomThumbSvg(?string $svg): void
    {
        $this->customThumbSvg = $svg;
    }

    public function setCustomThumbAlt(?string $alt): void
    {
        $this->customThumbAlt = $alt;
    }

    public function setCheckeredThumb(bool $checkered): void
    {
        $this->checkeredThumb = $checkered;
    }

    public function setRoundedThumb(bool $rounded): void
    {
        $this->roundedThumb = $rounded;
    }

    public function setAnimatedThumb(bool $animated): void
    {
        $this->animatedThumb = $animated;
    }

    #[Override]
    protected function thumbUrl(int $size, ImageTransformMode $mode = ImageTransformMode::Fit): ?string
    {
        $this->thumbRequests[] = [$size, $mode];

        return $this->customThumbUrl;
    }

    #[Override]
    protected function thumbSvg(): ?string
    {
        return $this->customThumbSvg;
    }

    #[Override]
    protected function thumbAlt(): ?string
    {
        return $this->customThumbAlt;
    }

    #[Override]
    protected function hasCheckeredThumb(): bool
    {
        return $this->checkeredThumb;
    }

    #[Override]
    protected function hasRoundedThumb(): bool
    {
        return $this->roundedThumb;
    }

    #[Override]
    protected function couldHaveAnimatedThumb(): bool
    {
        return $this->animatedThumb;
    }
}

describe('getThumbHtml', function () {
    test('preserves field precedence and only uses native URLs for empty field HTML', function (ImageTransformMode $mode, ?string $fieldHtml) {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('/native.jpg');
        $layout = Mockery::mock(FieldLayout::class);
        $layout->thumbFieldKey = 'layoutElement:thumbnail';
        $layout->shouldReceive('getThumbHtmlForElement')->once()
            ->with('layoutElement:thumbnail', $element, 120, $mode)->andReturn($fieldHtml);
        $element->thumbnailLayout = $layout;

        $html = $element->getThumbHtml(120, $mode);

        if ($fieldHtml) {
            expect($html)->toBe($fieldHtml)
                ->and($element->thumbRequests)->toBeEmpty();
        } else {
            expect($html)->toContainTag('craft-thumbnail', ['src' => '/native.jpg', 'mode' => $mode->value])
                ->and($element->thumbRequests)->toBe([[120, $mode], [240, $mode]]);
        }
    })->with([
        'crop' => ImageTransformMode::Crop,
        'fit' => ImageTransformMode::Fit,
        'stretch' => ImageTransformMode::Stretch,
        'letterbox' => ImageTransformMode::Letterbox,
    ])->with([
        'field HTML' => '<b>Field thumbnail</b>',
        'empty string fallback' => '',
        'null fallback' => [null],
    ]);

    test('defaults to fit for both responsive URLs', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('/thumbnail.jpg');

        $thumbnail = new Crawler($element->getThumbHtml(120))->filter('craft-thumbnail');

        expect($thumbnail->attr('mode'))->toBe('fit')
            ->and($element->thumbRequests)->toBe([[120, ImageTransformMode::Fit], [240, ImageTransformMode::Fit]]);
    });

    test('forwards and serializes explicit modes at both resolutions', function (ImageTransformMode $mode) {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('/thumbnail.jpg');

        $thumbnail = new Crawler($element->getThumbHtml(128, $mode))->filter('craft-thumbnail');

        expect($thumbnail->attr('mode'))->toBe($mode->value)
            ->and($thumbnail->attr('srcset'))->toBe('/thumbnail.jpg 128w, /thumbnail.jpg 256w')
            ->and($element->thumbRequests)->toBe([[128, $mode], [256, $mode]]);
    })->with([
        'crop' => ImageTransformMode::Crop,
        'fit' => ImageTransformMode::Fit,
        'stretch' => ImageTransformMode::Stretch,
        'letterbox' => ImageTransformMode::Letterbox,
    ]);

    test('does not apply image modes to SVG or null fallbacks', function (ImageTransformMode $mode) {
        $element = new TestThumbnailElement;
        expect($element->getThumbHtml(120, $mode))->toBeNull();

        $element->setCustomThumbSvg('<svg viewBox="0 0 40 20" preserveAspectRatio="xMinYMin meet"><circle r="10"/></svg>');
        $element->setCustomThumbAlt('Fallback');
        $svg = new Crawler($element->getThumbHtml(120, $mode));

        expect($svg->filter('craft-thumbnail')->count())->toBe(0)
            ->and($svg->filter('[mode]')->count())->toBe(0)
            ->and($svg->filter('svg')->count())->toBe(1)
            ->and($svg->filter('svg')->attr('viewBox'))->toBe('0 0 40 20')
            ->and($svg->filter('svg')->attr('preserveAspectRatio'))->toBe('xMinYMin meet')
            ->and($svg->filter('svg circle')->attr('r'))->toBe('10')
            ->and($svg->filter('svg')->attr('role'))->toBe('img')
            ->and($svg->filter('svg title')->text())->toBe('Fallback');
    })->with([
        'crop' => ImageTransformMode::Crop,
        'fit' => ImageTransformMode::Fit,
        'stretch' => ImageTransformMode::Stretch,
        'letterbox' => ImageTransformMode::Letterbox,
    ]);

    test('returns null when no thumb URL or SVG', function () {
        $element = new TestThumbnailElement;

        expect($element->getThumbHtml(100))->toBeNull();
    });

    test('returns HTML with thumb URL', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('https://example.com/thumb.jpg');

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('<craft-thumbnail');
        expect($html)->toContain('srcset');
        expect($html)->toContain('https://example.com/thumb.jpg');
    });

    test('includes checkered class when hasCheckeredThumb returns true', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('https://example.com/thumb.png');
        $element->setCheckeredThumb(true);

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('checkered');
    });

    test('includes rounded class when hasRoundedThumb returns true', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('https://example.com/thumb.jpg');
        $element->setRoundedThumb(true);

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('rounded');
    });

    test('includes alt text in data attribute', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('https://example.com/thumb.jpg');
        $element->setCustomThumbAlt('My thumbnail');

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('My thumbnail');
    });

    test('includes animated data attribute when couldHaveAnimatedThumb returns true', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('https://example.com/thumb.gif');
        $element->setAnimatedThumb(true);

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('animated');
    });

    test('falls back to SVG when no thumb URL', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbSvg('<svg><circle r="10"/></svg>');

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('<div');
        expect($html)->toContain('class="thumb w-[24px] h-[24px]"');
        expect($html)->toContain('<svg');
    });

    test('SVG includes alt text as title element', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbSvg('<svg><circle r="10"/></svg>');
        $element->setCustomThumbAlt('Icon description');

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('<title>Icon description</title>');
    });

    test('SVG has role img attribute', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbSvg('<svg><circle r="10"/></svg>');

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('role="img"');
    });

    test('SVG thumb includes rounded class when hasRoundedThumb returns true', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbSvg('<svg><circle r="10"/></svg>');
        $element->setRoundedThumb(true);

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('rounded');
    });
});

describe('default method values', function () {
    test('thumbUrl returns null by default', function () {
        $element = new class extends Element
        {
            #[Override]
            public static function displayName(): string
            {
                return 'Test';
            }

            public function exposeThumbUrl(int $size): ?string
            {
                return $this->thumbUrl($size);
            }
        };

        expect($element->exposeThumbUrl(100))->toBeNull();
    });

    test('thumbSvg returns null by default', function () {
        $element = new class extends Element
        {
            #[Override]
            public static function displayName(): string
            {
                return 'Test';
            }

            public function exposeThumbSvg(): ?string
            {
                return $this->thumbSvg();
            }
        };

        expect($element->exposeThumbSvg())->toBeNull();
    });

    test('thumbAlt returns null by default', function () {
        $element = new class extends Element
        {
            #[Override]
            public static function displayName(): string
            {
                return 'Test';
            }

            public function exposeThumbAlt(): ?string
            {
                return $this->thumbAlt();
            }
        };

        expect($element->exposeThumbAlt())->toBeNull();
    });

    test('hasCheckeredThumb returns false by default', function () {
        $element = new class extends Element
        {
            #[Override]
            public static function displayName(): string
            {
                return 'Test';
            }

            public function exposeHasCheckeredThumb(): bool
            {
                return $this->hasCheckeredThumb();
            }
        };

        expect($element->exposeHasCheckeredThumb())->toBeFalse();
    });

    test('hasRoundedThumb returns false by default', function () {
        $element = new class extends Element
        {
            #[Override]
            public static function displayName(): string
            {
                return 'Test';
            }

            public function exposeHasRoundedThumb(): bool
            {
                return $this->hasRoundedThumb();
            }
        };

        expect($element->exposeHasRoundedThumb())->toBeFalse();
    });

    test('couldHaveAnimatedThumb returns false by default', function () {
        $element = new class extends Element
        {
            #[Override]
            public static function displayName(): string
            {
                return 'Test';
            }

            public function exposeCouldHaveAnimatedThumb(): bool
            {
                return $this->couldHaveAnimatedThumb();
            }
        };

        expect($element->exposeCouldHaveAnimatedThumb())->toBeFalse();
    });
});
