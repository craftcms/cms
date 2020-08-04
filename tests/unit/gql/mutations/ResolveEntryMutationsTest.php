<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Stub\Expected;
use Craft;
use craft\elements\Entry;
use craft\gql\resolvers\mutations\Entry as EntryResolver;
use craft\models\EntryType;
use craft\models\GqlSchema;
use craft\models\Section;
use craft\test\mockclasses\elements\MockElementQuery;
use craft\test\TestCase;
use GraphQL\Type\Definition\ResolveInfo;

class ResolveEntryMutationsTest extends TestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     * @param $section
     * @param $entryType
     * @param arguments $
     * @param $expectedValues
     * @dataProvider identifyEntryDataProvider
     */
    public function testIdentifyEntry($section, $entryType, $arguments, $expectedValues)
    {
        $entryQuery = Craft::$app->getElements()->createElementQuery(Entry::class);
        $resolver = new EntryResolver();
        $resolver->setResolutionData('section', $section);
        $resolver->setResolutionData('entryType', $entryType);

        $this->invokeMethod($resolver, 'identifyEntry', [$entryQuery, $arguments]);

        foreach ($expectedValues as $key => $value) {
            $this->assertSame($value, $entryQuery->{$key});
        }
    }

    /**
     * @param $section
     * @param $entryType
     * @param array $arguments
     * @param array $entryAttributes
     * @param array $scopes
     * @param string $exception
     * @throws \ReflectionException
     * @dataProvider getEntryElementDataProvider
     */
    public function testGettingEntryElement($section, $entryType, $arguments = [], $entryAttributes = [], $scopes = [], $exception = '')
    {
        if ($exception) {
            $this->expectExceptionMessage($exception);
        }

        $this->tester->mockCraftMethods('gql', [
            'getActiveSchema' => $this->make(GqlSchema::class, [
                'scope' => $scopes
            ])
        ]);

        $this->tester->mockCraftMethods('elements', [
            'createElementQuery' => function() use ($arguments, $entryAttributes) {
                $query = MockElementQuery::generateSpecificQueryClass(Entry::class);
                $query->setReturnValues([(!empty($arguments['id']) && $arguments['id'] < 0) ? null : new Entry($entryAttributes)]);
                return $query;
            },
            'createElement' => new Entry()
        ]);

        $resolver = new EntryResolver();
        $resolver->setResolutionData('section', $section);
        $resolver->setResolutionData('entryType', $entryType);

        $entryElement = $this->invokeMethod($resolver, 'getEntryElement', [$arguments]);

        if (!empty($entryAttributes['typeId']) && $entryAttributes['typeId'] != $entryType->id) {
            $this->assertNull($entryElement->fieldLayoutId);
        }
    }

    /**
     * Test saving an entry does everything in the right order.
     *
     * @throws \Exception
     */
    public function testSaveEntry()
    {
        $testId = random_int(1, 1000);
        $entryElement = new Entry();

        $this->tester->mockCraftMethods('elements', [
            'createElementQuery' => Expected::once((new MockElementQuery())->setReturnValues([new Entry(['id' => $testId])]))
        ]);

        $resolver = $this->make(EntryResolver::class, [
            'getEntryElement' => Expected::once($entryElement),
            'populateElementWithData' => Expected::once($entryElement),
            'saveElement' => Expected::once($entryElement),
            'performStructureOperations' => Expected::once($entryElement),
        ]);

        $this->assertSame($testId, $resolver->saveEntry(null, [], null, $this->make(ResolveInfo::class))->id);
    }

    /**
     * Test deleting an entry checks for schema and calls the Element service.
     *
     * @throws \Exception
     */
    public function testDeleteEntry()
    {
        $this->tester->mockCraftMethods('elements', [
            'getElementById' => Expected::once(new Entry(['typeId' => 2])),
            'deleteElementById' => Expected::once(true)
        ]);

        $resolver = $this->make(EntryResolver::class, [
            'requireSchemaAction' => Expected::once(true)
        ]);

        $resolver->deleteEntry(null, ['id' => 2], null, $this->make(ResolveInfo::class));
    }

    /**
     * Test creating a draft checks for schema and creates the draft.
     *
     * @throws \Exception
     */
    public function testCreateDraft()
    {
        $testId = random_int(1, 1000);

        $this->tester->mockCraftMethods('elements', [
            'getElementById' => Expected::once(new Entry(['typeId' => 2, 'authorId' => 3])),
        ]);

        $this->tester->mockCraftMethods('drafts', [
            'createDraft' => Expected::once(new Entry(['draftId' => $testId])),
        ]);

        $resolver = $this->make(EntryResolver::class, [
            'requireSchemaAction' => Expected::once(true)
        ]);

        $this->assertSame($testId, $resolver->createDraft(null, ['id' => 2], null, $this->make(ResolveInfo::class)));
    }

    /**
     * Test publishing a draft.
     *
     * @throws \Exception
     */
    public function testPublishDraft()
    {
        $this->tester->mockCraftMethods('elements', [
            'createElementQuery' => Expected::once((new MockElementQuery())->setReturnValues([new Entry(['typeId' => 2])]))
        ]);
        $resolver = $this->make(EntryResolver::class, [
            'requireSchemaAction' => Expected::once(true)
        ]);
        $this->tester->mockCraftMethods('drafts', [
            'applyDraft' => Expected::once(new Entry(['id' => 1])),
        ]);

        $resolver->publishDraft(null, ['id' => 2], null, $this->make(ResolveInfo::class));
    }

    public function getEntryElementDataProvider()
    {
        return [
            // Create a new entry
            [
                new Section(['id' => 4]),
                new EntryType(['id' => 5, 'uid' => 'someUid']),
                [],
                [],
                ['entrytypes.someUid:create']
            ],
            // Retrieve entry
            [
                new Section(['id' => 4]),
                new EntryType(['id' => 5, 'uid' => 'someUid']),
                ['id' => 2],
                ['sectionId' => 4],
                ['entrytypes.someUid:save']
            ],
            // Test permission enforcement
            [
                new Section(['id' => 4]),
                new EntryType(['id' => 5, 'uid' => 'someUid']),
                ['id' => 2],
                ['sectionId' => 4],
                ['entrytypes.someUid:create'],
                'Unable to perform the action.'
            ],
            // Trigger the assertion in test that checks nulling layout id for a new section
            [
                new Section(['id' => 4]),
                new EntryType(['id' => 5, 'uid' => 'someUid']),
                ['id' => 2],
                ['sectionId' => 4, 'fieldLayoutId' => 8, 'typeId' => 4],
                ['entrytypes.someUid:save']
            ],
            // Impossible to change section of an entry
            [
                new Section(['id' => 4]),
                new EntryType(['id' => 5, 'uid' => 'someUid']),
                ['id' => 2],
                ['sectionId' => 5],
                ['entrytypes.someUid:save'],
                'Impossible to change the section of an existing entry'
            ],
            // Missing entry throws exception
            [
                new Section(['id' => 4]),
                new EntryType(['id' => 5, 'uid' => 'someUid']),
                ['id' => -22],
                [],
                ['entrytypes.someUid:save'],
                'No such entry exists'
            ],
        ];
    }

    public function identifyEntryDataProvider()
    {
        return [
            [
                new Section(),
                new EntryType(),
                ['draftId' => 8],
                ['draftId' => 8],
            ],
            [
                new Section(),
                new EntryType(),
                ['uid' => 8],
                ['uid' => 8],
            ],
            [
                new Section(),
                new EntryType(),
                ['id' => 8],
                ['id' => 8],
            ],
            [
                new Section(['type' => Section::TYPE_SINGLE]),
                new EntryType(['id' => 5]),
                [],
                ['typeId' => 5],
            ],
            [
                new Section(),
                new EntryType(),
                [],
                ['id' => -1],
            ],
        ];
    }
}
