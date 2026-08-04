<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql\mutations;

use craft\elements\GlobalSet;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\gql\mutations\Asset as AssetMutations;
use craft\gql\mutations\Category as CategoryMutations;
use craft\gql\mutations\Entry as EntryMutations;
use craft\gql\mutations\GlobalSet as GlobalSetMutations;
use craft\gql\mutations\Tag as TagMutations;
use craft\gql\types\elements\Asset as AssetGqlType;
use craft\gql\types\elements\Category as CategoryGqlType;
use craft\gql\types\elements\Entry as EntryGqlType;
use craft\gql\types\elements\GlobalSet as GlobalSetGqlType;
use craft\gql\types\elements\Tag as TagGqlType;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\GqlSchema;
use craft\models\Section;
use craft\models\TagGroup;
use craft\models\Volume;
use craft\test\TestCase;
use Exception;
use UnitTester;
use yii\base\InvalidConfigException;
use yii\base\UnknownMethodException;

class CreateMutationsTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected function _before(): void
    {
        // Mock all the things
        $this->tester->mockCraftMethods('volumes', [
            'getAllVolumes' => [
                new Volume(['uid' => 'uid', 'handle' => 'localVolume']),
            ],
        ]);

        $this->tester->mockCraftMethods('categories', [
            'getAllGroups' => [
                new CategoryGroup(['uid' => 'uid', 'handle' => 'someGroup']),
            ],
        ]);

        $this->tester->mockCraftMethods('tags', [
            'getAllTagGroups' => [
                new TagGroup(['uid' => 'uid', 'handle' => 'someGroup']),
            ],
        ]);

        $this->tester->mockCraftMethods('globals', [
            'getAllSets' => [
                new GlobalSet(['uid' => 'uid', 'handle' => 'gSet']),
            ],
        ]);

        $entryType = $this->make(EntryType::class, [
            'uid' => 'uid',
            'handle' => 'article',
        ]);

        $section = $this->make(Section::class, [
            'type' => Section::TYPE_CHANNEL,
            'uid' => 'sectionUid',
            'handle' => 'news',
            'getEntryTypes' => [
                $entryType,
            ],
        ]);

        $this->tester->mockCraftMethods('entries', [
            'getAllEntryTypes' => [
                $entryType,
            ],
            'getAllSections' => [
                $section,
            ],
        ]);
    }

    protected function _after(): void
    {
    }

    /**
     * For a list of scopes, test whether the right Asset mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     * @dataProvider assetMutationDataProvider
     * @throws InvalidConfigException
     */
    public function testCreateAssetMutations(array $scopes, array $mutationNames): void
    {
        $this->_mockScope($scopes);

        // Create mutations
        $mutations = AssetMutations::getMutations();
        $actualMutationNames = array_keys($mutations);

        // Verify
        sort($mutationNames);
        sort($actualMutationNames);

        self::assertEquals($mutationNames, $actualMutationNames);
    }

    /**
     * Check if a created save mutation for a given volume has expected arguments and returns a certain type
     */
    public function testCreateAssetSaveMutation(): void
    {
        $volume = $this->make(Volume::class, [
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [
                    new Number(['handle' => 'someNumberField']),
                ],
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $mutation = AssetMutations::createSaveMutation($volume);

        self::assertInstanceOf(AssetGqlType::class, $mutation['type']);
        self::assertArrayHasKey('someNumberField', $mutation['args']);
        self::assertArrayHasKey('id', $mutation['args']);
        self::assertArrayHasKey('_file', $mutation['args']);
    }

    /**
     * For a list of scopes, test whether the right Category mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     * @dataProvider categoryMutationDataProvider
     * @throws InvalidConfigException
     */
    public function testCreateCategoryMutations(array $scopes, array $mutationNames): void
    {
        $this->_mockScope($scopes);

        // Create mutations
        $mutations = CategoryMutations::getMutations();
        $actualMutationNames = array_keys($mutations);

        // Verify
        sort($mutationNames);
        sort($actualMutationNames);

        self::assertEquals($mutationNames, $actualMutationNames);
    }

    /**
     * Check if a created save mutation for a given category group has expected arguments and returns a certain type
     */
    public function testCreateCategorySaveMutation(): void
    {
        $categoryGroup = $this->make(CategoryGroup::class, [
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [
                    new PlainText(['handle' => 'someTextField']),
                ],
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $mutation = CategoryMutations::createSaveMutation($categoryGroup);

        self::assertInstanceOf(CategoryGqlType::class, $mutation['type']);
        self::assertArrayHasKey('someTextField', $mutation['args']);
        self::assertArrayHasKey('prependToRoot', $mutation['args']);
        self::assertArrayHasKey('title', $mutation['args']);
    }

    /**
     * For a list of scopes, test whether the right Tag mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     * @dataProvider tagMutationDataProvider
     * @throws InvalidConfigException
     */
    public function testCreateTagMutations(array $scopes, array $mutationNames): void
    {
        $this->_mockScope($scopes);

        // Create mutations
        $mutations = TagMutations::getMutations();
        $actualMutationNames = array_keys($mutations);

        // Verify
        sort($mutationNames);
        sort($actualMutationNames);

        self::assertEquals($mutationNames, $actualMutationNames);
    }

    /**
     * Check if a created save mutation for a given tag group has expected arguments and returns a certain type
     */
    public function testCreateTagSaveMutation(): void
    {
        $tagGroup = $this->make(TagGroup::class, [
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [
                    new PlainText(['handle' => 'someTextField']),
                ],
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $mutation = TagMutations::createSaveMutation($tagGroup);

        self::assertInstanceOf(TagGqlType::class, $mutation['type']);
        self::assertArrayHasKey('someTextField', $mutation['args']);
        self::assertArrayHasKey('uid', $mutation['args']);
    }

    /**
     * For a list of scopes, test whether the right global set mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     * @dataProvider globalSetMutationDataProvider
     * @throws InvalidConfigException
     */
    public function testCreateGlobalSetMutations(array $scopes, array $mutationNames): void
    {
        $this->_mockScope($scopes);

        // Create mutations
        $mutations = GlobalSetMutations::getMutations();
        $actualMutationNames = array_keys($mutations);

        // Verify
        sort($mutationNames);
        sort($actualMutationNames);

        self::assertEquals($mutationNames, $actualMutationNames);
    }

    /**
     * Check if a created save mutation for a given global set has expected arguments and returns a certain type
     */
    public function testCreateGlobalSetSaveMutation(): void
    {
        $globalSet = $this->make(GlobalSet::class, [
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [
                    new PlainText(['handle' => 'someTextField']),
                ],
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);

        $mutation = GlobalSetMutations::createSaveMutation($globalSet);

        self::assertInstanceOf(GlobalSetGqlType::class, $mutation['type']);
        self::assertArrayHasKey('someTextField', $mutation['args']);
        self::assertArrayNotHasKey('uid', $mutation['args']);
    }


    /**
     * For a list of scopes, test whether getting the right entry and draft mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     * @dataProvider entryMutationDataProvider
     * @throws InvalidConfigException
     */
    public function testCreateEntryMutations(array $scopes, array $mutationNames): void
    {
        $this->_mockScope($scopes);

        // Create mutations
        $mutations = EntryMutations::getMutations();
        $actualMutationNames = array_keys($mutations);

        // Verify
        sort($mutationNames);
        sort($actualMutationNames);

        self::assertEquals($mutationNames, $actualMutationNames);
    }

    /**
     * Check if a created save mutation for a given tag group has expected arguments and returns a certain type
     */
    public function testCreateEntrySaveMutation(): void
    {
        $typeA = $this->make(EntryType::class, [
            'handle' => 'typeA',
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [
                    new PlainText(['handle' => 'someTextField']),
                ],
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);
        $sectionA = new Section([
            'handle' => 'sectionA',
            'type' => Section::TYPE_SINGLE,
            'entryTypes' => [$typeA],
        ]);

        $typeB = $this->make(EntryType::class, [
            'handle' => 'typeB',
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [
                    new PlainText(['handle' => 'someTextField']),
                ],
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);
        $sectionB = new Section([
            'handle' => 'sectionB',
            'type' => Section::TYPE_CHANNEL,
            'entryTypes' => [$typeB],
        ]);

        $typeC = $this->make(EntryType::class, [
            'handle' => 'typeC',
            '__call' => fn($name) => match ($name) {
                'getCustomFields' => [
                    new PlainText(['handle' => 'someTextField']),
                ],
                default => throw new UnknownMethodException("Calling unknown method: $name()"),
            },
        ]);
        $sectionC = new Section([
            'handle' => 'sectionC',
            'type' => Section::TYPE_STRUCTURE,
            'entryTypes' => [$typeC],
        ]);

        [$saveMutation, $draftMutation] = EntryMutations::createSaveMutations($sectionA, $typeA, true);
        self::assertInstanceOf(EntryGqlType::class, $saveMutation['type']);
        self::assertInstanceOf(EntryGqlType::class, $draftMutation['type']);
        self::assertArrayHasKey('someTextField', $saveMutation['args']);
        self::assertArrayNotHasKey('id', $saveMutation['args']);
        self::assertArrayNotHasKey('authorId', $draftMutation['args']);

        [$saveMutation, $draftMutation] = EntryMutations::createSaveMutations($sectionB, $typeB, true);
        self::assertInstanceOf(EntryGqlType::class, $saveMutation['type']);
        self::assertInstanceOf(EntryGqlType::class, $draftMutation['type']);
        self::assertArrayHasKey('someTextField', $draftMutation['args']);
        self::assertArrayHasKey('uid', $saveMutation['args']);
        self::assertArrayHasKey('authorId', $draftMutation['args']);

        [$saveMutation, $draftMutation] = EntryMutations::createSaveMutations($sectionC, $typeC, true);
        self::assertInstanceOf(EntryGqlType::class, $saveMutation['type']);
        self::assertInstanceOf(EntryGqlType::class, $draftMutation['type']);
        self::assertStringContainsString('draft', $draftMutation['description']);
        self::assertArrayHasKey('appendTo', $saveMutation['args']);
        self::assertArrayHasKey('appendToRoot', $saveMutation['args']);
        self::assertArrayNotHasKey('appendToRoot', $draftMutation['args']);
    }

    public static function assetMutationDataProvider(): array
    {
        return [
            [
                ['volumes.uid:edit', 'volumes.uid:delete'],
                ['deleteAsset'],
            ],
            [
                ['volumes.uid:edit', 'volumes.uid:save', 'volumes.uid:delete'],
                ['deleteAsset', 'save_localVolume_Asset'],
            ],
            [
                ['volumes.uid:edit', 'volumes.uid:save'],
                ['save_localVolume_Asset'],
            ],
            [
                ['volumes.nope:edit', 'volumes.nope:save'],
                [],
            ],
        ];
    }

    public static function categoryMutationDataProvider(): array
    {
        return [
            [
                ['categorygroups.uid:edit', 'categorygroups.uid:delete'],
                ['deleteCategory'],
            ],
            [
                ['categorygroups.uid:edit', 'categorygroups.uid:save', 'categorygroups.uid:delete'],
                ['deleteCategory', 'save_someGroup_Category'],
            ],
            [
                ['categorygroups.uid:edit', 'categorygroups.uid:save'],
                ['save_someGroup_Category'],
            ],
            [
                ['categorygroups.nope:edit', 'categorygroups.nope:save'],
                [],
            ],
        ];
    }

    public static function tagMutationDataProvider(): array
    {
        return [
            [
                ['taggroups.uid:edit', 'taggroups.uid:delete'],
                ['deleteTag'],
            ],
            [
                ['taggroups.uid:edit', 'taggroups.uid:save', 'taggroups.uid:delete'],
                ['deleteTag', 'save_someGroup_Tag'],
            ],
            [
                ['taggroups.uid:edit', 'taggroups.uid:save'],
                ['save_someGroup_Tag'],
            ],
            [
                ['taggroups.nope:edit', 'taggroups.nope:save'],
                [],
            ],
        ];
    }

    public static function entryMutationDataProvider(): array
    {
        return [
            [
                ['sections.sectionUid:edit', 'sections.sectionUid:delete'],
                ['deleteEntry'],
            ],
            [
                ['sections.sectionUid:edit', 'sections.sectionUid:save', 'sections.sectionUid:delete'],
                ['deleteEntry', 'save_news_article_Entry', 'save_news_article_Draft', 'createDraft', 'publishDraft'],
            ],
            [
                ['sections.sectionUid:edit', 'sections.sectionUid:create'],
                ['save_news_article_Entry'],
            ],
            [
                ['sections.sectionUid:edit', 'sections.sectionUid:save'],
                ['save_news_article_Entry', 'save_news_article_Draft', 'createDraft', 'publishDraft'],
            ],
            [
                ['sections.nope:edit', 'sections.nope:save'],
                [],
            ],
        ];
    }

    public static function globalSetMutationDataProvider(): array
    {
        return [
            [
                ['globalsets.uid:edit'],
                ['save_gSet_GlobalSet'],
            ],
            [
                ['globalsets.uid:edit', 'globalsets.uid2:edit'],
                ['save_gSet_GlobalSet'],
            ],
        ];
    }

    /**
     * @param array $scopes
     * @throws Exception
     */
    private function _mockScope(array $scopes)
    {
        $this->tester->mockCraftMethods('gql', [
            'getActiveSchema' => $this->make(GqlSchema::class, [
                'scope' => $scopes,
            ]),
        ]);
    }
}
