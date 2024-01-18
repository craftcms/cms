<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use craft\base\Element;
use craft\elements\Entry;
use craft\events\DefineUrlEvent;
use craft\helpers\UrlHelper;
use craft\test\TestCase;
use UnitTester;

/**
 * Unit tests for entry elements
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.6
 */
class EntryElementTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @dataProvider getUrlDataProvider
     *
     * @param string|callable|null $expected
     * @param string|null $uri
     * @param callable|null $beforeEvent
     * @param callable|null $afterEvent
     */
    public function testGetUrl(string|callable|null $expected, ?string $uri, ?callable $beforeEvent, ?callable $afterEvent): void
    {
        $entry = new Entry();
        $entry->uri = $uri;

        if ($beforeEvent) {
            $entry->on(Element::EVENT_BEFORE_DEFINE_URL, $beforeEvent);
        }

        if ($afterEvent) {
            $entry->on(Element::EVENT_DEFINE_URL, $afterEvent);
        }

        if (is_callable($expected)) {
            $expected = $expected($entry->siteId);
        }

        self::assertSame($expected, $entry->getUrl());
    }

    public static function getUrlDataProvider(): array
    {
        return [
            [
                null,
                null,
                null,
                null,
            ],
            [
                fn(int $siteId) => UrlHelper::siteUrl('foo/bar', siteId: $siteId),
                'foo/bar',
                null,
                null,
            ],
            [
                fn(int $siteId) => UrlHelper::siteUrl('foo/bar', siteId: $siteId),
                'foo/bar',
                function(DefineUrlEvent $event) {
                    $event->url = null;
                },
                function(DefineUrlEvent $event) {
                    $event->url = null;
                },
            ],
            [
                null,
                'foo/bar',
                function(DefineUrlEvent $event) {
                    $event->url = null;
                    $event->handled = true;
                },
                null,
            ],
            [
                null,
                'foo/bar',
                null,
                function(DefineUrlEvent $event) {
                    $event->url = null;
                    $event->handled = true;
                },
            ],
            [
                '#',
                'foo/bar',
                function(DefineUrlEvent $event) {
                    $event->url = '#';
                },
                function(DefineUrlEvent $event) {
                    $event->url = null;
                },
            ],
            [
                fn(int $siteId) => UrlHelper::siteUrl('foo/bar', ['baz' => 'qux'], siteId: $siteId),
                'foo/bar',
                null,
                function(DefineUrlEvent $event) {
                    $event->url = UrlHelper::urlWithParams($event->url, ['baz' => 'qux']);
                },
            ],
        ];
    }
}
