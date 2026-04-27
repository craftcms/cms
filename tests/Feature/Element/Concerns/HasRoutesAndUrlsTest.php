<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\BeforeDefineUrl;
use CraftCms\Cms\Element\Events\DefineUrl;
use CraftCms\Cms\Element\Events\SetRoute;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\HtmlString;

use function Pest\Laravel\actingAs;

class TestRoutableElement extends Element
{
    protected ?string $customRoute = null;

    protected ?string $customUriFormat = null;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    public function setCustomRoute(?string $route): void
    {
        $this->customRoute = $route;
    }

    public function setCustomUriFormat(?string $uriFormat): void
    {
        $this->customUriFormat = $uriFormat;
    }

    #[Override]
    public function getUriFormat(): ?string
    {
        return $this->customUriFormat;
    }

    #[Override]
    protected function route(): array|string|null
    {
        return $this->customRoute;
    }
}

beforeEach(function () {
    $this->primarySiteId = Sites::getPrimarySite()->id;
});

describe('getUriFormat', function () {
    test('returns null by default', function () {
        $element = new TestRoutableElement;
        expect($element->getUriFormat())->toBeNull();
    });

    test('returns custom URI format when set', function () {
        $element = new TestRoutableElement;
        $element->setCustomUriFormat('blog/{slug}');

        expect($element->getUriFormat())->toBe('blog/{slug}');
    });
});

describe('getRoute', function () {
    test('returns null by default', function () {
        $element = new TestRoutableElement;
        expect($element->getRoute())->toBeNull();
    });

    test('returns custom route from route method', function () {
        $element = new TestRoutableElement;
        $element->setCustomRoute('my/custom/route');

        expect($element->getRoute())->toBe('my/custom/route');
    });

    test('SetRoute event can override route', function () {
        $element = new TestRoutableElement;
        $element->setCustomRoute('original-route');

        Event::listen(function (SetRoute $event) {
            $event->route = 'event-override-route';
        });

        expect($element->getRoute())->toBe('event-override-route');
    });

    test('SetRoute event can return null with handled flag', function () {
        $element = new TestRoutableElement;
        $element->setCustomRoute('original-route');

        Event::listen(function (SetRoute $event) {
            $event->route = null;
            $event->handled = true;
        });

        expect($element->getRoute())->toBeNull();
    });
});

describe('getIsHomepage', function () {
    test('returns false when uri is not homepage', function () {
        $element = new TestRoutableElement;
        $element->uri = 'some/path';

        expect($element->getIsHomepage())->toBeFalse();
    });

    test('returns true when uri is HOMEPAGE_URI', function () {
        $element = new TestRoutableElement;
        $element->uri = Element::HOMEPAGE_URI;

        expect($element->getIsHomepage())->toBeTrue();
    });
});

describe('getUrl', function () {
    test('returns null when no uri is set', function () {
        $element = new TestRoutableElement;
        expect($element->getUrl())->toBeNull();
    });

    test('returns site URL when uri is set', function () {
        $element = new TestRoutableElement;
        $element->siteId = $this->primarySiteId;
        $element->uri = 'test-path';

        $url = $element->getUrl();

        expect($url)->toContain('test-path');
    });

    test('returns site root when uri is homepage', function () {
        $element = new TestRoutableElement;
        $element->siteId = $this->primarySiteId;
        $element->uri = Element::HOMEPAGE_URI;

        $url = $element->getUrl();

        expect($url)->not->toContain(Element::HOMEPAGE_URI);
    });

    test('BeforeDefineUrl event can set custom URL', function () {
        $element = new TestRoutableElement;
        $element->siteId = $this->primarySiteId;
        $element->uri = 'test-path';

        Event::listen(function (BeforeDefineUrl $event) {
            $event->url = 'https://custom-url.com/path';
        });

        expect($element->getUrl())->toBe('https://custom-url.com/path');
    });

    test('DefineUrl event can modify URL', function () {
        $element = new TestRoutableElement;
        $element->siteId = $this->primarySiteId;
        $element->uri = 'test-path';

        Event::listen(function (DefineUrl $event) {
            $event->url = $event->url.'?modified=true';
        });

        $url = $element->getUrl();

        expect($url)->toContain('modified=true');
    });
});

describe('getLink', function () {
    test('returns null when no URL', function () {
        $element = new TestRoutableElement;
        expect($element->getLink())->toBeNull();
    });

    test('returns Markup with anchor tag when URL exists', function () {
        $element = new TestRoutableElement;
        $element->siteId = $this->primarySiteId;
        $element->uri = 'test-path';

        $link = $element->getLink();

        expect($link)->toBeInstanceOf(HtmlString::class);
        expect((string) $link)->toContain('<a');
        expect((string) $link)->toContain('test-path');
    });
});

describe('integration with Entry element', function () {
    test('Entry getUrl returns proper URL with URI', function () {
        actingAs(User::findOne());
        $entryModel = EntryModel::factory()->create();
        $entry = Entry::find()->id($entryModel->id)->one();

        expect($entry)->not->toBeNull();
        $entry->uri = 'my-entry-slug';

        $url = $entry->getUrl();

        expect($url)->toContain('my-entry-slug');
    });

    test('Entry getIsHomepage returns false for regular entries', function () {
        actingAs(User::findOne());
        $entryModel = EntryModel::factory()->create();
        $entry = Entry::find()->id($entryModel->id)->one();

        expect($entry)->not->toBeNull();
        expect($entry->getIsHomepage())->toBeFalse();
    });
});
