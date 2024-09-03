<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\User;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use Illuminate\Support\Collection;

/**
 * Unit tests for ElementCollection
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.10.0
 */
class ElementCollectionTest extends TestCase
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
        ];
    }

    public function testFind(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        $first = $collection->first();
        self::assertInstanceOf(Entry::class, $first);
        self::assertSame($first, $collection->find($first));
        self::assertNull($collection->find(User::find()->one()));
        self::assertSame([$first], $collection->find([$first->id])->all());
        self::assertTrue($collection->find([-1])->isEmpty());
        self::assertSame($first, $collection->find($first->id));
        self::assertNull($collection->find(-1));
    }

    public function testWith(): void
    {
        $collection = Entry::find()->limit(1)->collect();
        self::assertFalse($collection->first()->hasEagerLoadedElements('foo'));
        $collection->with('foo');
        self::assertTrue($collection->first()->hasEagerLoadedElements('foo'));
    }

    public function testContains(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        self::assertTrue($collection->contains('title', 'Theories of life'));
        self::assertTrue($collection->contains(fn(Entry $entry) => $entry->title === 'Theories of life'));
        self::assertFalse($collection->contains(fn(Entry $entry) => false));
        $first = $collection->first();
        self::assertInstanceOf(Entry::class, $first);
        self::assertTrue($collection->contains($first));
        self::assertFalse($collection->contains(User::find()->one()));
        self::assertTrue($collection->contains($first->id));
        self::assertFalse($collection->contains(-1));
        self::assertFalse($collection->contains('title'));
    }

    public function testIds(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        $ids = $collection->map(fn(Entry $entry) => $entry->id)->all();
        self::assertSame($ids, $collection->ids()->all());
    }

    public function testMerge(): void
    {
        /** @var ElementCollection<Entry|User> $collection */
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        $first = $collection->first();
        self::assertInstanceOf(Entry::class, $first);
        $user = User::find()->one();
        self::assertInstanceOf(User::class, $user);
        $merged = $collection->merge([$user]);
        self::assertTrue($merged->contains($first));
        self::assertTrue($merged->contains($user));
        self::assertEquals($collection->count() + 1, $merged->count());
    }

    public function testMap(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        $mapped = $collection->map(fn(Entry $entry) => new Entry());
        self::assertInstanceOf(ElementCollection::class, $mapped);
        $mapped = $collection->map(fn(Entry $entry) => $entry->id);
        self::assertSame(Collection::class, $mapped::class);
    }

    public function testMapWithKeys(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        $mapped = $collection->mapWithKeys(fn(Entry $entry, int|string $key) => [$entry->id => new Entry()]);
        self::assertInstanceOf(ElementCollection::class, $mapped);
        $mapped = $collection->mapWithKeys(fn(Entry $entry, int|string $key) => [$entry->id => $entry->id]);
        self::assertInstanceOf(Collection::class, $mapped);
        self::assertNotInstanceOf(ElementCollection::class, $mapped);
    }

    public function testFresh(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        $collection->each(function(Entry $entry) {
            $entry->title .= 'edit';
        });
        self::assertFalse($collection->contains(fn(Entry $entry) => !str_ends_with($entry->title, 'edit')));
        $fresh = $collection->fresh();
        self::assertSame($collection->count(), $fresh->count());
        self::assertTrue($fresh->contains(fn(Entry $entry) => !str_ends_with($entry->title, 'edit')));
    }

    public function testDiff(): void
    {
        $collection1 = Entry::find()->limit(4)->collect();
        self::assertInstanceOf(ElementCollection::class, $collection1);
        self::assertSame(4, $collection1->count());
        $collection2 = Entry::find()->offset(3)->collect();
        self::assertInstanceOf(ElementCollection::class, $collection2);
        self::assertTrue($collection2->isNotEmpty());
        $diff = $collection1->diff($collection2->all());
        self::assertSame(3, $diff->count());
    }

    public function testIntersect(): void
    {
        $collection1 = Entry::find()->limit(4)->collect();
        self::assertInstanceOf(ElementCollection::class, $collection1);
        self::assertSame(4, $collection1->count());
        $collection2 = Entry::find()->offset(3)->collect();
        self::assertInstanceOf(ElementCollection::class, $collection2);
        self::assertTrue($collection2->isNotEmpty());
        $intersect = $collection1->intersect($collection2->all());
        self::assertSame(1, $intersect->count());
    }

    public function testUnique(): void
    {
        $collection = Entry::find()->limit(4)->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        $count = $collection->count();
        $collection->push(...$collection->all());
        self::assertSame($count * 2, $collection->count());
        $unique = $collection->unique();
        self::assertSame($count, $unique->count());
    }

    public function testOnly(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        self::assertNotEquals(1, $collection->count());
        $first = $collection->first();
        self::assertInstanceOf(Entry::class, $first);
        self::assertEquals(1, $collection->only([$first->id])->count());
        self::assertEquals(1, $collection->only($first->id)->count());
    }

    public function testExcept(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        $count = $collection->count();
        $first = $collection->first();
        self::assertInstanceOf(Entry::class, $first);
        self::assertEquals($count - 1, $collection->except([$first->id])->count());
        self::assertEquals($count - 1, $collection->except($first->id)->count());
    }

    public function testBaseMethods(): void
    {
        $collection = Entry::find()->collect();
        self::assertInstanceOf(ElementCollection::class, $collection);
        self::assertSame(Collection::class, get_class($collection->countBy(fn(Entry $entry) => $entry->sectionId)));
        self::assertSame(Collection::class, get_class($collection->collapse()));
        self::assertSame(Collection::class, get_class($collection->flatten(1)));
        self::assertSame(Collection::class, get_class($collection->keys()));
        self::assertSame(Collection::class, get_class($collection->pad(100, null)));
        self::assertSame(Collection::class, get_class($collection->pluck('title')));
        self::assertSame(Collection::class, get_class($collection->zip($collection->ids())));
    }
}
