<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;

class TestThumbnailElement extends Element
{
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
    protected function thumbUrl(int $size): ?string
    {
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
    test('returns null when no thumb URL or SVG', function () {
        $element = new TestThumbnailElement;

        expect($element->getThumbHtml(100))->toBeNull();
    });

    test('returns HTML with thumb URL', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbUrl('https://example.com/thumb.jpg');

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('<div');
        expect($html)->toContain('class="thumb"');
        expect($html)->toContain('data-srcset');
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

        expect($html)->toContain('data-animated');
    });

    test('falls back to SVG when no thumb URL', function () {
        $element = new TestThumbnailElement;
        $element->setCustomThumbSvg('<svg><circle r="10"/></svg>');

        $html = $element->getThumbHtml(100);

        expect($html)->toContain('<div');
        expect($html)->toContain('class="thumb"');
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
