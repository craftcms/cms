<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\gql\resolvers\elements\Category as CategoryResolver;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\resolvers\elements\GlobalSet as GlobalSetResolver;
use craft\gql\resolvers\elements\MatrixBlock as MatrixBlockResolver;
use craft\gql\resolvers\elements\Tag as TagResolver;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\records\CategoryGroup;
use craft\records\Element;
use craft\records\EntryType;
use craft\records\GlobalSet;
use craft\records\Section;
use craft\records\Structure;
use craft\records\TagGroup;
use craft\records\UserGroup;
use craft\records\Volume;
use craft\test\TestCase;
use UnitTester;

class PrepareQueryTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    private Volume $_volume;
    private Structure $_structure;
    private CategoryGroup $_categoryGroup;
    private Section $_section;
    private EntryType $_entryType;
    private Element $_element;
    private GlobalSet $_globalSet;
    private TagGroup $_tagGroup;
    private UserGroup $_userGroup;


    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        // Mock the GQL token
        $this->tester->mockMethods(
            Craft::$app,
            'gql',
            [
                'getActiveSchema' => $this->make(GqlSchema::class, [
                    'scope' => [
                        'volumes.' . self::VOLUME_UID . ':read',
                        'categorygroups.' . self::CATEGORY_GROUP_UID . ':read',
                        'sections.' . self::SECTION_UID . ':read',
                        'entrytypes.' . self::ENTRY_TYPE_UID . ':read',
                        'globalsets.' . self::GLOBAL_SET_UID . ':read',
                        'taggroups.' . self::TAG_GROUP_UID . ':read',
                        'usergroups.' . self::USER_GROUP_UID . ':read',
                    ],
                ]),
            ]
        );

        $this->_setupAssets();
        $this->_setupCategories();
        $this->_setupEntries();
        $this->_setupGlobals();
        $this->_setupTags();
        $this->_setupUsers();
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        $this->_volume->delete();
        $this->_structure->delete();
        $this->_categoryGroup->delete();
        $this->_section->delete();
        $this->_entryType->delete();
        $this->_element->delete();
        $this->_globalSet->delete();
        $this->_tagGroup->delete();
        $this->_userGroup->delete();
    }

    public const VOLUME_UID = 'volume-uid';
    public const CATEGORY_GROUP_UID = 'categoryGroup-uid';
    public const SECTION_UID = 'section-uid';
    public const ENTRY_TYPE_UID = 'entryType-uid';
    public const GLOBAL_SET_UID = 'globalSet-uid';
    public const TAG_GROUP_UID = 'tagGroup-uid';
    public const USER_GROUP_UID = 'userGroup-uid';

    /**
     * Test relational field query preparation
     *
     * @param string $resolverClass The resolver class to test
     * @phpstan-param class-string $resolverClass
     * @param array $preparationArguments The arguments to pass to the `prepareQuery` method
     * @param callable $testFunction The test function to determine the result.
     * @param callable|null $testLoader The callable that will set up the test conditions
     * @dataProvider relationalFieldQueryPreparationProvider
     */
    public function testRelationalFieldQueryPreparation(string $resolverClass, array $preparationArguments, callable $testFunction, callable $testLoader = null): void
    {
        // Set up the test
        if ($testLoader) {
            $testLoader();
        }

        // Call the `prepareQuery` method.
        $result = call_user_func_array([$resolverClass, 'prepareQuery'], $preparationArguments);

        // Test if results valid
        self::assertTrue($testFunction($result));
    }

    public function relationalFieldQueryPreparationProvider(): array
    {
        /**
         * Tests:
         * 1) Eager-loaded field (if applicable)
         * 2) Arguments applied as passed
         * 3) `andWhere` limitation applied
         */

        return [
            // Assets
            [
                AssetResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], function($result) {
                    return $result === ['foo', 'bar'];
                },
            ],
            [
                AssetResolver::class, [null, ['volumeId' => 2, 'folderId' => 5]], function($result) {
                    return $result->volumeId == 2 && $result->folderId == 5;
                },
            ],
            [
                AssetResolver::class, [null, []], function($result) {
                    return $result->where[0] === 'in' && !empty($result->where[2]);
                },
            ],

            // Category
            [
                CategoryResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], function($result) {
                    return $result === ['foo', 'bar'];
                },
            ],
            [
                CategoryResolver::class, [null, ['groupId' => 2]], function($result) {
                    return $result->groupId == 2;
                },
            ],
            [
                CategoryResolver::class, [null, []], function($result) {
                    return $result->where[0] === 'in' && !empty($result->where[2]);
                },
            ],

            // Entries
            [
                EntryResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], function($result) {
                    return $result === ['foo', 'bar'];
                },
            ],
            [
                EntryResolver::class, [null, ['sectionId' => 2, 'typeId' => 5]], function($result) {
                    return $result->sectionId == 2 && $result->typeId == 5;
                },
            ],
            [
                EntryResolver::class, [null, []], function($result) {
                    return $result->where[0] === 'and' && !empty($result->where[2]);
                },
            ],

            // Global Sets
            [
                GlobalSetResolver::class, [null, ['handle' => 'foo']], function($result) {
                    return $result->handle == 'foo';
                },
            ],
            [
                GlobalSetResolver::class, [null, []], function($result) {
                    return $result->where[0] === 'in' && !empty($result->where[2]);
                },
            ],

            // Tags
            [
                TagResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], function($result) {
                    return $result === ['foo', 'bar'];
                },
            ],
            [
                TagResolver::class, [null, ['groupId' => 2]], function($result) {
                    return $result->groupId == 2;
                },
            ],
            [
                TagResolver::class, [null, []], function($result) {
                    return $result->where[0] === 'in' && !empty($result->where[2]);
                },
            ],

            // Users
            [
                UserResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], function($result) {
                    return $result === ['foo', 'bar'];
                },
            ],
            [
                UserResolver::class, [null, []], function($result) {
                    return !empty($result->groupId);
                },
            ],

            // Matrix Blocks
            [
                MatrixBlockResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], function($result) {
                    return $result === ['foo', 'bar'];
                },
            ],
            [
                MatrixBlockResolver::class, [null, ['fieldId' => 2, 'typeId' => 5]], function($result) {
                    return $result->fieldId == 2 && $result->typeId == 5;
                },
            ],

        ];
    }

    private function _setupAssets()
    {
        $this->_volume = new Volume([
            'uid' => self::VOLUME_UID,
            'name' => StringHelper::randomString(),
            'handle' => StringHelper::randomString(),
            'fs' => 'fake',
        ]);

        $this->_volume->save();

        $volumesService = Craft::$app->getVolumes();

        $this->tester->mockCraftMethods('volumes', [
            'getVolumeByUid' => function($uid) use ($volumesService) {
                if ($uid === self::VOLUME_UID) {
                    return new \craft\models\Volume([
                        'id' => $this->_volume->id,
                        'uid' => self::VOLUME_UID,
                        'name' => $this->_volume->name,
                        'handle' => $this->_volume->handle,
                    ]);
                }
                return $volumesService->getVolumeByUid($uid);
            },
        ]);
    }

    private function _setupCategories()
    {
        $this->_structure = new Structure();
        $this->_structure->save();

        $this->_categoryGroup = new CategoryGroup([
            'uid' => self::CATEGORY_GROUP_UID,
            'name' => StringHelper::randomString(),
            'handle' => StringHelper::randomString(),
            'structureId' => $this->_structure->id,
        ]);

        $this->_categoryGroup->save();

        $categoriesService = Craft::$app->getCategories();

        $this->tester->mockCraftMethods('categories', [
            'getGroupByUid' => function($uid) use ($categoriesService) {
                if ($uid === self::CATEGORY_GROUP_UID) {
                    return new \craft\models\CategoryGroup([
                        'id' => $this->_categoryGroup->id,
                        'uid' => self::CATEGORY_GROUP_UID,
                        'name' => $this->_categoryGroup->name,
                        'handle' => $this->_categoryGroup->handle,
                        'structureId' => $this->_structure->id,
                    ]);
                }
                return $categoriesService->getGroupByUid($uid);
            },
        ]);
    }

    private function _setupEntries()
    {
        $this->_section = new Section([
            'uid' => self::SECTION_UID,
            'name' => StringHelper::randomString(),
            'handle' => StringHelper::randomString(),
            'type' => 'channel',
            'enableVersioning' => true,
            'propagationMethod' => StringHelper::randomString(),
        ]);
        $this->_section->save();

        $this->_entryType = new EntryType([
            'uid' => self::ENTRY_TYPE_UID,
            'name' => StringHelper::randomString(),
            'handle' => StringHelper::randomString(),
            'sectionId' => $this->_section->id,
            'hasTitleField' => false,
        ]);
        $this->_entryType->save();
    }

    private function _setupGlobals()
    {
        $this->_element = new Element([
            'type' => StringHelper::randomString(),
            'enabled' => true,
            'archived' => false,
        ]);
        $this->_element->save();

        $this->_globalSet = new GlobalSet([
            'uid' => self::GLOBAL_SET_UID,
            'name' => StringHelper::randomString(),
            'handle' => StringHelper::randomString(),
            'id' => $this->_element->id,
        ]);
        $this->_globalSet->save();
    }

    private function _setupTags()
    {
        $this->_tagGroup = new TagGroup([
            'uid' => self::TAG_GROUP_UID,
            'name' => StringHelper::randomString(),
            'handle' => StringHelper::randomString(),
        ]);

        $this->_tagGroup->save();

        $tagsService = Craft::$app->getTags();

        $this->tester->mockCraftMethods('tags', [
            'getTagGroupByUid' => function($uid) use ($tagsService) {
                if ($uid === self::TAG_GROUP_UID) {
                    return new \craft\models\TagGroup([
                        'id' => $this->_tagGroup->id,
                        'uid' => self::TAG_GROUP_UID,
                        'name' => $this->_tagGroup->name,
                        'handle' => $this->_tagGroup->handle,
                    ]);
                }
                return $tagsService->getTagGroupByUid($uid);
            },
        ]);
    }

    private function _setupUsers()
    {
        $this->_userGroup = new UserGroup([
            'uid' => self::USER_GROUP_UID,
            'name' => StringHelper::randomString(),
            'handle' => StringHelper::randomString(),
        ]);

        $this->_userGroup->save();
    }
}
