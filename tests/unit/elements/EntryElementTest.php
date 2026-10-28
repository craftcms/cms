<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use craft\base\Element;
use craft\elements\db\EagerLoadPlan;
use craft\elements\Entry;
use craft\elements\User;
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

    /**
     * `owner`/`primaryOwner` are magic properties that resolve to `getOwner()`/`getPrimaryOwner()`
     * (via `NestedElementTrait`, used by nested entries such as Matrix blocks). Since eager-loading
     * those handles bypasses the generic `Element::$_eagerLoadedElements` array (see
     * `NestedElementTrait::setEagerLoadedElements()`), property access should always match the
     * dedicated getters, whether or not the owner has been eager-loaded.
     *
     * @dataProvider ownerHandleDataProvider
     */
    public function testOwnerPropertyMatchesGetter(string $handle): void
    {
        // No owner set at all
        $nested = new Entry(['id' => 1]);
        $getter = $handle === 'owner' ? $nested->getOwner() : $nested->getPrimaryOwner();
        self::assertNull($getter);
        self::assertSame($getter, $nested->$handle);

        // Owner set directly, not via eager-loading
        $owner = new Entry(['id' => 100]);
        if ($handle === 'owner') {
            $nested->setOwner($owner);
        } else {
            $nested->setPrimaryOwner($owner);
        }
        $getter = $handle === 'owner' ? $nested->getOwner() : $nested->getPrimaryOwner();
        self::assertSame($owner, $getter);
        self::assertSame($getter, $nested->$handle);

        // Owner eager-loaded
        $nested2 = new Entry(['id' => 2]);
        $plan = new EagerLoadPlan(['handle' => $handle]);
        $nested2->setEagerLoadedElements($handle, [$owner], $plan);
        $getter = $handle === 'owner' ? $nested2->getOwner() : $nested2->getPrimaryOwner();
        self::assertSame($owner, $getter);
        self::assertSame($getter, $nested2->$handle);

        // No owner eager-loaded (empty result)
        $nested3 = new Entry(['id' => 3]);
        $nested3->setEagerLoadedElements($handle, [], $plan);
        $getter = $handle === 'owner' ? $nested3->getOwner() : $nested3->getPrimaryOwner();
        self::assertNull($getter);
        self::assertSame($getter, $nested3->$handle);
    }

    public static function ownerHandleDataProvider(): array
    {
        return [
            'owner' => ['owner'],
            'primaryOwner' => ['primaryOwner'],
        ];
    }

    /**
     * `author`/`authors` are magic properties that resolve to `getAuthor()`/`getAuthors()`. Since
     * eager-loading those handles bypasses the generic `Element::$_eagerLoadedElements` array (see
     * `Entry::setEagerLoadedElements()`), property access should always match the dedicated
     * getters, whether or not the authors have been eager-loaded.
     */
    public function testAuthorAuthorsPropertyMatchesGetter(): void
    {
        // No authors set at all
        $entry = new Entry(['id' => 4]);
        self::assertNull($entry->getAuthor());
        self::assertSame($entry->getAuthor(), $entry->author);
        self::assertSame($entry->getAuthors(), $entry->authors);

        // Authors set directly, not via eager-loading
        $author1 = new User(['id' => 400]);
        $author2 = new User(['id' => 401]);
        $entry->setAuthors([$author1, $author2]);
        self::assertSame($author1, $entry->getAuthor());
        self::assertSame($entry->getAuthor(), $entry->author);
        self::assertSame($entry->getAuthors(), $entry->authors);

        // Authors eager-loaded
        $entry2 = new Entry(['id' => 5]);
        $plan = new EagerLoadPlan(['handle' => 'authors']);
        $entry2->setEagerLoadedElements('authors', [$author1, $author2], $plan);
        self::assertSame($author1, $entry2->getAuthor());
        self::assertSame($entry2->getAuthor(), $entry2->author);
        self::assertSame($entry2->getAuthors(), $entry2->authors);

        // No authors eager-loaded (empty result)
        $entry3 = new Entry(['id' => 6]);
        $entry3->setEagerLoadedElements('authors', [], $plan);
        self::assertNull($entry3->getAuthor());
        self::assertSame($entry3->getAuthor(), $entry3->author);
        self::assertSame($entry3->getAuthors(), $entry3->authors);
    }
}
