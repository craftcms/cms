<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
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

class PrepareQueryTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $_volume;
    private $_structure;
    private $_categoryGroup;
    private $_section;
    private $_entryType;
    private $_element;
    private $_globalSet;
    private $_tagGroup;
    private $_userGroup;


    /**
     * @inheritdoc
     */
    protected function _before()
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
                    ]
                ])
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
    protected function _after()
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

    const VOLUME_UID = 'volume-uid';
    const CATEGORY_GROUP_UID = 'categoryGroup-uid';
    const SECTION_UID = 'section-uid';
    const ENTRY_TYPE_UID = 'entryType-uid';
    const GLOBAL_SET_UID = 'globalSet-uid';
    const TAG_GROUP_UID = 'tagGroup-uid';
    const USER_GROUP_UID = 'userGroup-uid';

    /**
     * Test relational field query preparation
     *
     * @param string $resolverClass The resolver class to test
     * @param array $preparationArguments The arguments to pass to the `prepareQuery` method
     * @param callable $testFunction The test function to determine the result.
     * @param callable|null $testLoader The callable that will set up the test conditions
     *
     * @dataProvider relationalFieldQueryPreparationProvider
     */
    public function testRelationalFieldQueryPreparation(string $resolverClass, array $preparationArguments, callable $testFunction, callable $testLoader = null)
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

    public function relationalFieldQueryPreparationProvider()
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
                }
            ],
            [
                AssetResolver::class, [null, ['volumeId' => 2, 'folderId' => 5]], function($result) {
                    return $result->volumeId == 2 && $result->folderId == 5;
                }
            ],
            [
                AssetResolver::class, [null, []], function($result) {
                    return $result->where[0] === 'in' && !empty($result->where[2]);
                }
            ],

            // Category
            [
                CategoryResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], function($result) {
                    return $result === ['foo', 'bar'];
                }
            ],
            [
                CategoryResolver::class, [null, ['groupId' => 2]], function($result) {
                    return $result->groupId == 2;
                }
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
                }
            ],
            [
                EntryResolver::class, [null, ['sectionId' => 2, 'typeId' => 5]], function($result) {
                    return $result->sectionId == 2 && $result->typeId == 5;
                }
            ],
            [
                EntryResolver::class, [null, []], function($result) {
                    return $result->where[0] === 'and' && !empty($result->where[2]);
                }
            ],

            // Global Sets
            [
                GlobalSetResolver::class, [null, ['handle' => 'foo']], function($result) {
                    return $result->handle == 'foo';
                }
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
                }
            ],
            [
                TagResolver::class, [null, ['groupId' => 2]], function($result) {
                    return $result->groupId == 2;
                }
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
                }
            ],
            [
                UserResolver::class, [null, ['groupId' => 2, 'email' => 'foo@bar.org']], function($result) {
                    return $result->groupId == 2 && $result->email == 'foo@bar.org';
            }
            ],
            [
                UserResolver::class, [null, []], function($result) {
                    return !empty($result->groupBy);
                }
            ],

            // Matrix Blocks
            [
                MatrixBlockResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], function($result) {
                    return $result === ['foo', 'bar'];
                }
            ],
            [
                MatrixBlockResolver::class, [null, ['fieldId' => 2, 'typeId' => 5]], function($result) {
                    return $result->fieldId == 2 && $result->typeId == 5;
                }
            ],

        ];
    }

    private function _setupAssets()
    {
        $this->_volume = new Volume([
            'uid' => self::VOLUME_UID,
            'name' => StringHelper::randomString(),
            'handle' => StringHelper::randomString(),
            'type' => StringHelper::randomString(),
            'hasUrls' => false,
        ]);

        $this->_volume->save();
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
