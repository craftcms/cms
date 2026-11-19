<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\test;

use Codeception\Test\Unit;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\EntryWithMatrixFixture;
use Illuminate\Support\Arr;
use yii\base\ErrorException;
use yii\db\Expression;

/**
 * Unit tests for eager loading content
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.10
 */
class EagerLoadingTest extends TestCase
{
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
                'createRevisions' => true,
            ],
            'entry-with-matrix' => [
                'class' => EntryWithMatrixFixture::class,
            ],
        ];
    }

    /**
     * Test eager loading simple relational field, matrix field,
     * relational field within a matrix field
     *
     * @return void
     */
    public function testEagerLoadingScenario1(): void
    {
        // getting the entry
        $entry = Entry::find()
            ->title('Matrix with relational field')
            ->with([
                'relatedEntry', // field exists and is part of the layout
                'matrixSecond', // field exists and is part of the layout
                'matrixSecond.matrixLayout3:entriesSubfield', // field exists and is part of the layout
                'matrixFirst', // field exists and is NOT part of the layout
            ])
            ->one();

        self::assertNotNull($entry);

        // check if simple relational field e.g. related entry was eager loaded
        self::assertNotEmpty($entry->getEagerLoadedElements('relatedEntry'));

        // check if matrix field was eager loaded
        $matrixSecond = $entry->getEagerLoadedElements('matrixSecond');
        self::assertNotEmpty($matrixSecond);

        // check if relational field inside a matrix field was eager loaded
        self::assertNotEmpty($matrixSecond[0]->getEagerLoadedElements('entriesSubfield'));

        // check if eager loading field that's not part of the layout returns empty result
        self::assertEmpty($matrixSecond[1]->getEagerLoadedElements('entriesSubfield'));
        self::assertEmpty($entry->getEagerLoadedElements('matrixFirst'));
    }

    /**
     * Test if eager loading element attributes (e.g. revisions, descendants)
     * returns results (#12646, #12645)
     *
     * @return void
     */
    public function testEagerLoadingScenario2(): void
    {
        $entry = Entry::find()->sectionId(1004)->with(['revisions'])->one();
        self::assertNotEmpty($entry->getEagerLoadedElements('revisions'));
    }

    /**
     * Test eager loading relational field that doesn't exist,
     * exists, has content and is part of the layout, exists and is not part of the layout
     *
     * @return void
     */
    public function testEagerLoadingScenario3(): void
    {
        // get entries from section 1000
        // that section has field-layout: field-layout-1002----------------uid
        // which doesn't contain the 'relatedEntry' field created for section 1006
        $entries = Entry::find()->sectionId(1000)->limit(2)->all();

        // try to eager load a field that exists,
        // and is part of the layout for $entries that we retrieved
        try {
            Craft::$app->getElements()->eagerLoadElements(
                Entry::class,
                $entries,
                'matrixFirst' // field exists but is not part of the layout
            );
        } catch (ErrorException) {
            $this->fail();
        }

        // try to eager load a field that doesn't exist
        try {
            Craft::$app->getElements()->eagerLoadElements(
                Entry::class,
                $entries,
                'fieldDoesntExist' // field exists but is not part of the layout
            );
        } catch (ErrorException) {
            $this->fail();
        }

        // try to eager load a field that exists,
        // but is not part of the layout for $entries that we retrieved
        // this would throw a \base\yii\ErrorException on 4.3.8.1;
        // see https://github.com/craftcms/cms/issues/12648 for more info
        try {
            Craft::$app->getElements()->eagerLoadElements(
                Entry::class,
                $entries,
                'relatedEntry' // field exists but is not part of the layout
            );
        } catch (ErrorException) {
            $this->fail();
        }

        $this->expectNotToPerformAssertions();
    }

    /**
     * Test if removing a relational field, that was populated with content, from the layout
     * returns empty results
     *
     * @return void
     * @throws \yii\base\Exception
     */
    public function testEagerLoadingScenario4(): void
    {
        $this->_removeFieldFromLayout('field-layout-1003----------------uid', 'relatedEntry');

        $entry = Entry::find()
            ->title('Matrix with relational field')
            ->with([
                'relatedEntry', // field exists, was part of the layout and was populated with data, then was removed from the layout
            ])
            ->one();

        self::assertNotNull($entry);
        self::assertEmpty($entry->getEagerLoadedElements('relatedEntry'));
    }

    /**
     * Remove field by handle from layout by UUID
     *
     * @param string $layoutUid
     * @param string $fieldHandle
     * @return void
     * @throws \yii\base\Exception
     */
    private function _removeFieldFromLayout(string $layoutUid, string $fieldHandle): void
    {
        $fieldsService = Craft::$app->getFields();
        $fieldLayout = $fieldsService->getLayoutByUid($layoutUid);
        $tabs = $fieldLayout->getTabs();
        $layoutElements = $tabs[0]->getElements();
        foreach ($layoutElements as $key => $element) {
            if ($element instanceof CustomField) {
                if ($element->getField()->handle === $fieldHandle) {
                    unset($layoutElements[$key]);
                }
            }
        }

        $tabs[0]->setElements($layoutElements);
        $fieldsService->saveLayout($fieldLayout);
    }

    /**
     * Test that custom `orderBy` criteria is applied to eager-loaded elements, as long as it’s safe
     * (see https://github.com/craftcms/cms/issues/19992)
     *
     * @return void
     */
    public function testEagerLoadingOrderBy(): void
    {
        $nestedEntryIds = $this->eagerLoadMatrixIds();
        self::assertCount(2, $nestedEntryIds);
        self::assertTrue($nestedEntryIds[0] < $nestedEntryIds[1]);
        $reversedIds = array_reverse($nestedEntryIds);

        self::assertSame($reversedIds, $this->eagerLoadMatrixIds(['orderBy' => 'id desc']));
        self::assertSame($reversedIds, $this->eagerLoadMatrixIds(['orderBy' => ['elements.id' => SORT_DESC]]));
        self::assertSame($reversedIds, $this->eagerLoadMatrixIds(['orderBy' => new Expression('[[elements.id]] desc')]));
        self::assertSame([$reversedIds[0]], $this->eagerLoadMatrixIds(['orderBy' => 'id desc', 'limit' => 1]));

        // unsafe values should be ignored
        self::assertSame($nestedEntryIds, $this->eagerLoadMatrixIds(['orderBy' => 'id desc, (select 1)']));
        self::assertSame($nestedEntryIds, $this->eagerLoadMatrixIds(['orderBy' => ['(select 1)' => SORT_DESC]]));
    }

    /**
     * Test that `orderBy` and `inReverse` criteria are only applied once for eager-loaded elements
     *
     * @return void
     */
    public function testEagerLoadingOrderByInReverse(): void
    {
        $nestedEntryIds = $this->eagerLoadMatrixIds();
        self::assertCount(2, $nestedEntryIds);

        self::assertSame($nestedEntryIds, $this->eagerLoadMatrixIds(['orderBy' => 'id desc', 'inReverse' => true]));
        self::assertSame([$nestedEntryIds[0]], $this->eagerLoadMatrixIds(['orderBy' => 'id desc', 'inReverse' => true, 'limit' => 1]));
    }

    /**
     * Test that custom `orderBy` criteria is applied to lazily eager-loaded elements via `eagerly()`
     * (see https://github.com/craftcms/cms/issues/19992)
     *
     * @return void
     */
    public function testEagerlyOrderBy(): void
    {
        $nestedEntryIds = $this->eagerLoadMatrixIds();
        self::assertCount(2, $nestedEntryIds);
        $reversedIds = array_reverse($nestedEntryIds);

        self::assertSame($nestedEntryIds, $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query));
        self::assertSame($reversedIds, $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query->orderBy('id desc')));
        self::assertSame($reversedIds, $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query->orderBy(['elements.id' => SORT_DESC])));
        self::assertSame([$reversedIds[0]], $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query->orderBy('id desc')->limit(1)));
        self::assertSame($reversedIds, $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query->inReverse()));
        self::assertSame($nestedEntryIds, $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query->orderBy('id desc')->inReverse()));
        self::assertSame([$reversedIds[1]], $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query->orderBy('id desc')->offset(1)));
        self::assertSame([$nestedEntryIds[0]], $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query->limit(1)));
        self::assertSame([$reversedIds[0]], $this->eagerlyLoadMatrixIds(fn(EntryQuery $query) => $query->orderBy('id desc'), true));
    }

    /**
     * Test that every map returned for a handle gets loaded, when the maps cover more than one element type
     *
     * @return void
     */
    public function testEagerLoadingMapsWithMultipleElementTypes(): void
    {
        [$entryA, $entryB] = Entry::find()->limit(2)->orderBy(['elements.id' => SORT_ASC])->all();
        $user = User::find()->one();
        self::assertNotNull($entryB);
        self::assertNotNull($user);

        $siteId = Craft::$app->getSites()->getPrimarySite()->id;

        $maps = [
            'list of maps' => [
                [
                    'elementType' => Entry::class,
                    'map' => [
                        ['source' => 1, 'target' => $entryA->id],
                        ['source' => 2, 'target' => $entryB->id],
                    ],
                ],
                [
                    'elementType' => User::class,
                    'map' => [
                        ['source' => 1, 'target' => $user->id],
                    ],
                ],
            ],
            'map with mixed target types' => [
                'map' => [
                    ['source' => 1, 'target' => $entryA->id],
                    ['source' => 2, 'target' => $entryB->id],
                    ['source' => 1, 'target' => $user->id],
                ],
            ],
        ];

        foreach ($maps as $name => $map) {
            EagerLoadingTestSourceElement::$map = $map;
            $sourceA = new EagerLoadingTestSourceElement(['id' => 1, 'siteId' => $siteId]);
            $sourceB = new EagerLoadingTestSourceElement(['id' => 2, 'siteId' => $siteId]);

            Craft::$app->getElements()->eagerLoadElements(EagerLoadingTestSourceElement::class, [$sourceA, $sourceB], [
                'mixed',
                ['path' => 'mixed as mixedCount', 'count' => true],
                ['path' => 'mixed as firstMixed', 'criteria' => ['limit' => 1]],
                ['path' => 'mixed as otherMixed', 'criteria' => ['offset' => 1]],
            ]);

            $ids = fn(ElementInterface $source, string $alias = 'mixed') => array_map(
                fn(ElementInterface $element) => [$element::class, $element->id],
                $source->getEagerLoadedElements($alias)->all(),
            );

            self::assertSame([[Entry::class, $entryA->id], [User::class, $user->id]], $ids($sourceA), $name);
            self::assertSame([[Entry::class, $entryB->id]], $ids($sourceB), $name);
            self::assertSame(2, $sourceA->getEagerLoadedElementCount('mixedCount'), $name);
            self::assertSame(1, $sourceB->getEagerLoadedElementCount('mixedCount'), $name);

            // the plan's offset and limit apply to each source's combined elements
            self::assertSame([[Entry::class, $entryA->id]], $ids($sourceA, 'firstMixed'), $name);
            self::assertSame([[User::class, $user->id]], $ids($sourceA, 'otherMixed'), $name);
            self::assertSame([], $ids($sourceB, 'otherMixed'), $name);
        }
    }

    /**
     * Test that each map keeps its own limit when several maps are combined, and that the plan's limit
     * can be a numeric string
     *
     * @return void
     */
    public function testEagerLoadingMapsWithOwnLimits(): void
    {
        $entries = Entry::find()->limit(4)->orderBy(['elements.id' => SORT_ASC])->ids();
        self::assertCount(4, $entries);

        EagerLoadingTestSourceElement::$map = [
            [
                'elementType' => Entry::class,
                'map' => [
                    ['source' => 1, 'target' => $entries[0]],
                    ['source' => 1, 'target' => $entries[1]],
                ],
                'criteria' => ['limit' => 1],
            ],
            [
                'elementType' => Entry::class,
                'map' => [
                    ['source' => 1, 'target' => $entries[2]],
                    ['source' => 1, 'target' => $entries[3]],
                ],
            ],
        ];
        $source = new EagerLoadingTestSourceElement([
            'id' => 1,
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
        ]);

        Craft::$app->getElements()->eagerLoadElements(EagerLoadingTestSourceElement::class, [$source], [
            'mixed',
            ['path' => 'mixed as limited', 'criteria' => ['limit' => '2']],
        ]);

        $ids = fn(string $alias) => array_map(
            fn(ElementInterface $element) => $element->id,
            $source->getEagerLoadedElements($alias)->all(),
        );

        self::assertSame([$entries[0], $entries[2], $entries[3]], $ids('mixed'));
        self::assertSame([$entries[0], $entries[2]], $ids('limited'));
    }

    /**
     * Test that a map split up by element type keeps its mapping order, and that the plan's `inReverse`
     * applies to each source's combined elements
     *
     * @return void
     */
    public function testEagerLoadingMapsKeepMappingOrder(): void
    {
        [$entryA, $entryB] = Entry::find()->limit(2)->orderBy(['elements.id' => SORT_ASC])->all();
        $user = User::find()->one();
        self::assertNotNull($entryB);
        self::assertNotNull($user);

        EagerLoadingTestSourceElement::$map = [
            'map' => [
                ['source' => 1, 'target' => $entryA->id],
                ['source' => 1, 'target' => $user->id],
                ['source' => 1, 'target' => $entryB->id],
            ],
        ];
        $source = new EagerLoadingTestSourceElement([
            'id' => 1,
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
        ]);

        Craft::$app->getElements()->eagerLoadElements(EagerLoadingTestSourceElement::class, [$source], [
            'mixed',
            ['path' => 'mixed as page', 'criteria' => ['offset' => 1, 'limit' => 1]],
            ['path' => 'mixed as reversed', 'criteria' => ['inReverse' => true]],
            ['path' => 'mixed as reversedPage', 'criteria' => ['inReverse' => true, 'offset' => 1, 'limit' => 1]],
            ['path' => 'mixed as negativeLimit', 'criteria' => ['limit' => -1]],
            ['path' => 'mixed as zeroLimit', 'criteria' => ['offset' => 1, 'limit' => 0]],
            ['path' => 'mixed as negativeOffset', 'criteria' => ['offset' => -1]],
        ]);

        $ids = fn(string $alias) => array_map(
            fn(ElementInterface $element) => [$element::class, $element->id],
            $source->getEagerLoadedElements($alias)->all(),
        );

        $a = [Entry::class, $entryA->id];
        $u = [User::class, $user->id];
        $b = [Entry::class, $entryB->id];
        self::assertSame([$a, $u, $b], $ids('mixed'));
        self::assertSame([$u], $ids('page'));
        self::assertSame([$b, $u, $a], $ids('reversed'));
        self::assertSame([$u], $ids('reversedPage'));

        // nonpositive limits and offsets are ignored, as they are with a single map
        self::assertSame([$a, $u, $b], $ids('negativeLimit'));
        self::assertSame([$u, $b], $ids('zeroLimit'));
        self::assertSame([$a, $u, $b], $ids('negativeOffset'));

        // a map's own inReverse is kept
        EagerLoadingTestSourceElement::$map = [
            [
                'elementType' => Entry::class,
                'map' => [
                    ['source' => 1, 'target' => $entryA->id],
                    ['source' => 1, 'target' => $entryB->id],
                ],
                'criteria' => ['inReverse' => true],
            ],
            [
                'elementType' => User::class,
                'map' => [
                    ['source' => 1, 'target' => $user->id],
                ],
            ],
        ];
        $source = new EagerLoadingTestSourceElement([
            'id' => 1,
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
        ]);

        Craft::$app->getElements()->eagerLoadElements(EagerLoadingTestSourceElement::class, [$source], [
            'mixed',
            ['path' => 'mixed as firstMixed', 'criteria' => ['limit' => 1]],
        ]);

        $ids = fn(string $alias) => array_map(
            fn(ElementInterface $element) => [$element::class, $element->id],
            $source->getEagerLoadedElements($alias)->all(),
        );

        self::assertSame([$b, $a, $u], $ids('mixed'));
        self::assertSame([$b], $ids('firstMixed'));

        // a map's own inReverse only affects the sources it covers
        EagerLoadingTestSourceElement::$map = [
            [
                'map' => [
                    ['source' => 1, 'target' => $entryA->id],
                    ['source' => 1, 'target' => $user->id],
                    ['source' => 1, 'target' => $entryB->id],
                ],
            ],
            [
                'elementType' => Entry::class,
                'map' => [
                    ['source' => 2, 'target' => $entryA->id],
                    ['source' => 2, 'target' => $entryB->id],
                ],
                'criteria' => ['inReverse' => true],
            ],
        ];
        $siteId = Craft::$app->getSites()->getPrimarySite()->id;
        $sourceA = new EagerLoadingTestSourceElement(['id' => 1, 'siteId' => $siteId]);
        $sourceB = new EagerLoadingTestSourceElement(['id' => 2, 'siteId' => $siteId]);

        Craft::$app->getElements()->eagerLoadElements(EagerLoadingTestSourceElement::class, [$sourceA, $sourceB], [
            'mixed',
        ]);

        $sourceIds = fn(ElementInterface $source) => array_map(
            fn(ElementInterface $element) => [$element::class, $element->id],
            $source->getEagerLoadedElements('mixed')->all(),
        );

        self::assertSame([$a, $u, $b], $sourceIds($sourceA));
        self::assertSame([$b, $a], $sourceIds($sourceB));

        // an interleaved map and a reversed map for the same source each keep their own order
        [, , $entryC, $entryD] = Entry::find()->limit(4)->orderBy(['elements.id' => SORT_ASC])->all();
        self::assertNotNull($entryD);
        $c = [Entry::class, $entryC->id];
        $d = [Entry::class, $entryD->id];

        EagerLoadingTestSourceElement::$map = [
            [
                'map' => [
                    ['source' => 1, 'target' => $entryA->id],
                    ['source' => 1, 'target' => $user->id],
                    ['source' => 1, 'target' => $entryB->id],
                ],
            ],
            [
                'elementType' => Entry::class,
                'map' => [
                    ['source' => 1, 'target' => $entryC->id],
                    ['source' => 1, 'target' => $entryD->id],
                ],
                'criteria' => ['inReverse' => true],
            ],
        ];
        $sourceA = new EagerLoadingTestSourceElement(['id' => 1, 'siteId' => $siteId]);

        Craft::$app->getElements()->eagerLoadElements(EagerLoadingTestSourceElement::class, [$sourceA], [
            'mixed',
            ['path' => 'mixed as page', 'criteria' => ['offset' => 2, 'limit' => 2]],
        ]);

        $sourceIds = fn(string $alias) => array_map(
            fn(ElementInterface $element) => [$element::class, $element->id],
            $sourceA->getEagerLoadedElements($alias)->all(),
        );

        self::assertSame([$a, $u, $b, $d, $c], $sourceIds('mixed'));
        self::assertSame([$b, $d], $sourceIds('page'));
    }

    /**
     * @param callable(EntryQuery): EntryQuery $modifyQuery
     * @param bool $one Whether to execute the query with `one()` rather than `all()`
     * @return int[]
     */
    private function eagerlyLoadMatrixIds(callable $modifyQuery, bool $one = false): array
    {
        // eagerly() only kicks in when the source element was fetched along with other elements
        $entries = Entry::find()
            ->title(['Matrix with relational field', 'Theories of life'])
            ->all();

        self::assertCount(2, $entries);
        $entry = Arr::first($entries, fn(Entry $entry) => $entry->title === 'Matrix with relational field');

        /** @var EntryQuery $query */
        $query = $entry->getFieldValue('matrixSecond');
        $query = $modifyQuery($query)->eagerly();
        $nestedEntries = $one ? array_filter([$query->one()]) : $query->all();

        // make sure they were actually eager-loaded
        self::assertTrue($query->wasEagerLoaded("eagerly:$query->eagerLoadHandle"));

        return array_map(fn(ElementInterface $element) => $element->id, $nestedEntries);
    }

    /**
     * @param array $criteria
     * @return int[]
     */
    private function eagerLoadMatrixIds(array $criteria = []): array
    {
        $entry = Entry::find()
            ->title('Matrix with relational field')
            ->with([
                ['matrixSecond', $criteria],
            ])
            ->one();

        self::assertNotNull($entry);

        return array_map(fn(ElementInterface $element) => $element->id, $entry->getEagerLoadedElements('matrixSecond')->all());
    }
}

/**
 * A source element for [[EagerLoadingTest]] that returns a preset eager-loading map.
 */
class EagerLoadingTestSourceElement extends Element
{
    public static array $map = [];

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        return static::$map;
    }
}
