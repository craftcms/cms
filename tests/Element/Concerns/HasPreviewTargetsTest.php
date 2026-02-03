<?php

declare(strict_types=1);

use craft\events\RegisterPreviewTargetsEvent;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Facades\Sites;
use yii\base\Event;

class TestPreviewTargetsElement extends Element
{
    protected array $customPreviewTargets = [];

    protected ?string $elementUrl = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    public function setCustomPreviewTargets(array $targets): void
    {
        $this->customPreviewTargets = $targets;
    }

    public function setElementUrl(?string $url): void
    {
        $this->elementUrl = $url;
    }

    #[Override]
    public function getUrl(): ?string
    {
        return $this->elementUrl;
    }

    #[Override]
    protected function previewTargets(): array
    {
        if (! empty($this->customPreviewTargets)) {
            return $this->customPreviewTargets;
        }

        return parent::previewTargets();
    }
}

beforeEach(function () {
    $this->primarySiteId = Sites::getPrimarySite()->id;
});

afterEach(function () {
    Event::off(TestPreviewTargetsElement::class, Element::EVENT_REGISTER_PREVIEW_TARGETS);
});

describe('getPreviewTargets', function () {
    test('returns empty array when element has no URL', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;

        expect($element->getPreviewTargets())->toBe([]);
    });

    test('returns primary page target when element has URL', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;
        $element->setElementUrl('https://example.com/my-page');

        $targets = $element->getPreviewTargets();

        expect($targets)->toHaveCount(1);
        expect($targets[0]['label'])->toContain('Primary');
        expect($targets[0]['url'])->toContain('my-page');
        expect($targets[0]['refresh'])->toBeTrue();
    });

    test('returns custom preview targets', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;
        $element->setCustomPreviewTargets([
            [
                'label' => 'Desktop Preview',
                'url' => 'https://example.com/preview/desktop',
            ],
            [
                'label' => 'Mobile Preview',
                'url' => 'https://example.com/preview/mobile',
            ],
        ]);

        $targets = $element->getPreviewTargets();

        expect($targets)->toHaveCount(2);
        expect($targets[0]['label'])->toBe('Desktop Preview');
        expect($targets[1]['label'])->toBe('Mobile Preview');
    });

    test('normalizes urlFormat to url', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;
        $element->slug = 'my-slug';
        $element->setCustomPreviewTargets([
            [
                'label' => 'Dynamic Preview',
                'urlFormat' => 'https://example.com/preview/{slug}',
            ],
        ]);

        $targets = $element->getPreviewTargets();

        expect($targets)->toHaveCount(1);
        expect($targets[0])->not->toHaveKey('urlFormat');
        expect($targets[0]['url'])->toContain('my-slug');
    });

    test('filters out targets without url', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;
        $element->setCustomPreviewTargets([
            [
                'label' => 'Has URL',
                'url' => 'https://example.com/preview',
            ],
            [
                'label' => 'No URL',
            ],
        ]);

        $targets = $element->getPreviewTargets();

        expect($targets)->toHaveCount(1);
        expect($targets[0]['label'])->toBe('Has URL');
    });

    test('sets refresh to true by default', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;
        $element->setCustomPreviewTargets([
            [
                'label' => 'Preview',
                'url' => 'https://example.com/preview',
            ],
        ]);

        $targets = $element->getPreviewTargets();

        expect($targets[0]['refresh'])->toBeTrue();
    });

    test('preserves explicit refresh value', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;
        $element->setCustomPreviewTargets([
            [
                'label' => 'No Refresh',
                'url' => 'https://example.com/preview',
                'refresh' => false,
            ],
        ]);

        $targets = $element->getPreviewTargets();

        expect($targets[0]['refresh'])->toBeFalse();
    });

    test('EVENT_REGISTER_PREVIEW_TARGETS can add targets', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;
        $element->setElementUrl('https://example.com/original');

        Event::on(
            TestPreviewTargetsElement::class,
            Element::EVENT_REGISTER_PREVIEW_TARGETS,
            function (RegisterPreviewTargetsEvent $event) {
                $event->previewTargets[] = [
                    'label' => 'Added by Event',
                    'url' => 'https://example.com/event-added',
                ];
            }
        );

        $targets = $element->getPreviewTargets();

        expect($targets)->toHaveCount(2);
        expect($targets[1]['label'])->toBe('Added by Event');
    });

    test('EVENT_REGISTER_PREVIEW_TARGETS can modify existing targets', function () {
        $element = new TestPreviewTargetsElement;
        $element->siteId = $this->primarySiteId;
        $element->setCustomPreviewTargets([
            [
                'label' => 'Original',
                'url' => 'https://example.com/original',
            ],
        ]);

        Event::on(
            TestPreviewTargetsElement::class,
            Element::EVENT_REGISTER_PREVIEW_TARGETS,
            function (RegisterPreviewTargetsEvent $event) {
                $event->previewTargets[0]['label'] = 'Modified by Event';
            }
        );

        $targets = $element->getPreviewTargets();

        expect($targets[0]['label'])->toBe('Modified by Event');
    });
});
