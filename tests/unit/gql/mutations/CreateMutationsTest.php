<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Test\Unit;
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
use craft\volumes\Local;

class CreateMutationsTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        // Mock all the things
        $this->tester->mockCraftMethods('volumes', [
            'getAllVolumes' => [
                new Local(['uid' => 'uid', 'handle' => 'localVolume'])
            ]
        ]);

        $this->tester->mockCraftMethods('categories', [
            'getAllGroups' => [
                new CategoryGroup(['uid' => 'uid', 'handle' => 'someGroup'])
            ]
        ]);

        $this->tester->mockCraftMethods('tags', [
            'getAllTagGroups' => [
                new TagGroup(['uid' => 'uid', 'handle' => 'someGroup'])
            ]
        ]);

        $this->tester->mockCraftMethods('globals', [
            'getAllSets' => [
                new GlobalSet(['uid' => 'uid', 'handle' => 'gSet'])
            ]
        ]);

        $this->tester->mockCraftMethods('sections', [
            'getAllEntryTypes' => [
                $this->make(EntryType::class, [
                    'uid' => 'uid',
                    'handle' => 'article',
                    'getSection' => new Section([
                        'type' => Section::TYPE_CHANNEL,
                        'uid' => 'sectionUid',
                        'handle' => 'news'
                    ])
                ])
            ]
        ]);

    }

    protected function _after()
    {
    }

    /**
     * For a list of scopes, test whether the right Asset mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     *
     * @dataProvider assetMutationDataProvider
     * @throws \yii\base\InvalidConfigException
     */
   public function testCreateAssetMutations(array $scopes, array $mutationNames)
   {
       $this->_mockScope($scopes);

       // Create mutations
       $mutations = AssetMutations::getMutations();
       $actualMutationNames = array_keys($mutations);

       // Verify
       sort($mutationNames);
       sort($actualMutationNames);

       $this->assertEquals($mutationNames, $actualMutationNames);
   }

    /**
     * Check if a created save mutation for a given volume has expected arguments and returns a certain type
     */
   public function testCreateAssetSaveMutation()
   {
       $volume = $this->make(Local::class, [
               '__call' => function($name, $args) {
                   return [
                       new Number(['handle' => 'someNumberField']),
                   ];
               }
           ]
       );

       $mutation = AssetMutations::createSaveMutation($volume);

       $this->assertInstanceOf(AssetGqlType::class, $mutation['type']);
       $this->assertArrayHasKey('someNumberField', $mutation['args']);
       $this->assertArrayHasKey('id', $mutation['args']);
       $this->assertArrayHasKey('_file', $mutation['args']);
   }

    /**
     * For a list of scopes, test whether the right Category mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     *
     * @dataProvider categoryMutationDataProvider
     * @throws \yii\base\InvalidConfigException
     */
   public function testCreateCategoryMutations(array $scopes, array $mutationNames)
   {
       $this->_mockScope($scopes);

       // Create mutations
       $mutations = CategoryMutations::getMutations();
       $actualMutationNames = array_keys($mutations);

       // Verify
       sort($mutationNames);
       sort($actualMutationNames);

       $this->assertEquals($mutationNames, $actualMutationNames);
   }

    /**
     * Check if a created save mutation for a given category group has expected arguments and returns a certain type
     */
   public function testCreateCategorySaveMutation()
   {
       $categoryGroup = $this->make(CategoryGroup::class, [
               '__call' => function($name, $args) {
                   return [
                       new PlainText(['handle' => 'someTextField']),
                   ];
               }
           ]
       );

       $mutation = CategoryMutations::createSaveMutation($categoryGroup);

       $this->assertInstanceOf(CategoryGqlType::class, $mutation['type']);
       $this->assertArrayHasKey('someTextField', $mutation['args']);
       $this->assertArrayHasKey('prependToRoot', $mutation['args']);
       $this->assertArrayHasKey('title', $mutation['args']);
   }

    /**
     * For a list of scopes, test whether the right Tag mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     *
     * @dataProvider tagMutationDataProvider
     * @throws \yii\base\InvalidConfigException
     */
   public function testCreateTagMutations(array $scopes, array $mutationNames)
   {
       $this->_mockScope($scopes);

       // Create mutations
       $mutations = TagMutations::getMutations();
       $actualMutationNames = array_keys($mutations);

       // Verify
       sort($mutationNames);
       sort($actualMutationNames);

       $this->assertEquals($mutationNames, $actualMutationNames);
   }

    /**
     * Check if a created save mutation for a given tag group has expected arguments and returns a certain type
     */
   public function testCreateTagSaveMutation()
   {
       $tagGroup = $this->make(TagGroup::class, [
               '__call' => function($name, $args) {
                   return [
                       new PlainText(['handle' => 'someTextField']),
                   ];
               }
           ]
       );

       $mutation = TagMutations::createSaveMutation($tagGroup);

       $this->assertInstanceOf(TagGqlType::class, $mutation['type']);
       $this->assertArrayHasKey('someTextField', $mutation['args']);
       $this->assertArrayHasKey('uid', $mutation['args']);
   }

    /**
     * For a list of scopes, test whether the right global set mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     *
     * @dataProvider globalSetMutationDataProvider
     * @throws \yii\base\InvalidConfigException
     */
   public function testCreateGlobalSetMutations(array $scopes, array $mutationNames)
   {
       $this->_mockScope($scopes);

       // Create mutations
       $mutations = GlobalSetMutations::getMutations();
       $actualMutationNames = array_keys($mutations);

       // Verify
       sort($mutationNames);
       sort($actualMutationNames);

       $this->assertEquals($mutationNames, $actualMutationNames);
   }

    /**
     * Check if a created save mutation for a given global set has expected arguments and returns a certain type
     */
   public function testCreateGlobalSetSaveMutation()
   {
       $globalSet = $this->make(GlobalSet::class, [
               '__call' => function($name, $args) {
                   return [
                       new PlainText(['handle' => 'someTextField']),
                   ];
               }
           ]
       );

       $mutation = GlobalSetMutations::createSaveMutation($globalSet);

       $this->assertInstanceOf(GlobalSetGqlType::class, $mutation['type']);
       $this->assertArrayHasKey('someTextField', $mutation['args']);
       $this->assertArrayNotHasKey('uid', $mutation['args']);
   }


    /**
     * For a list of scopes, test whether getting the right entry and draft mutations are created.
     *
     * @param array $scopes
     * @param array $mutationNames
     *
     * @dataProvider entryMutationDataProvider
     * @throws \yii\base\InvalidConfigException
     */
    public function testCreateEntryMutations(array $scopes, array $mutationNames)
    {
        $this->_mockScope($scopes);

        // Create mutations
        $mutations = EntryMutations::getMutations();
        $actualMutationNames = array_keys($mutations);

        // Verify
        sort($mutationNames);
        sort($actualMutationNames);

        $this->assertEquals($mutationNames, $actualMutationNames);
    }

    /**
     * Check if a created save mutation for a given tag group has expected arguments and returns a certain type
     */
    public function testCreateEntrySaveMutation()
    {
        $single = $this->make(EntryType::class, [
                '__call' => function($name, $args) {
                    return [
                        new PlainText(['handle' => 'someTextField']),
                    ];
                },
                'getSection' => new Section(['type' => Section::TYPE_SINGLE])
            ]
        );

        $channel = $this->make(EntryType::class, [
                '__call' => function($name, $args) {
                    return [
                        new PlainText(['handle' => 'someTextField']),
                    ];
                },
                'getSection' => new Section(['type' => Section::TYPE_CHANNEL])
            ]
        );

        $structure = $this->make(EntryType::class, [
                '__call' => function($name, $args) {
                    return [
                        new PlainText(['handle' => 'someTextField']),
                    ];
                },
                'getSection' => new Section(['type' => Section::TYPE_STRUCTURE])
            ]
        );

        list($saveMutation, $draftMutation) = EntryMutations::createSaveMutations($single, true);
        $this->assertInstanceOf(EntryGqlType::class, $saveMutation['type']);
        $this->assertInstanceOf(EntryGqlType::class, $draftMutation['type']);
        $this->assertArrayHasKey('someTextField', $saveMutation['args']);
        $this->assertArrayNotHasKey('id', $saveMutation['args']);
        $this->assertArrayNotHasKey('authorId', $draftMutation['args']);

        list($saveMutation, $draftMutation) = EntryMutations::createSaveMutations($channel, true);
        $this->assertInstanceOf(EntryGqlType::class, $saveMutation['type']);
        $this->assertInstanceOf(EntryGqlType::class, $draftMutation['type']);
        $this->assertArrayHasKey('someTextField', $draftMutation['args']);
        $this->assertArrayHasKey('uid', $saveMutation['args']);
        $this->assertArrayHasKey('authorId', $draftMutation['args']);

        list($saveMutation, $draftMutation) = EntryMutations::createSaveMutations($structure, true);
        $this->assertInstanceOf(EntryGqlType::class, $saveMutation['type']);
        $this->assertInstanceOf(EntryGqlType::class, $draftMutation['type']);
        $this->assertContains('draft', $draftMutation['description']);
        $this->assertArrayHasKey('appendTo', $saveMutation['args']);
        $this->assertArrayHasKey('appendToRoot', $saveMutation['args']);
        $this->assertArrayNotHasKey('appendToRoot', $draftMutation['args']);
    }

    public function assetMutationDataProvider()
    {
        return [
            [
                ['volumes.uid:edit', 'volumes.uid:delete'],
                ['deleteAsset']
            ],
            [
                ['volumes.uid:edit', 'volumes.uid:save', 'volumes.uid:delete'],
                ['deleteAsset', 'save_localVolume_Asset']
            ],
            [
                ['volumes.uid:edit', 'volumes.uid:save'],
                ['save_localVolume_Asset']
            ],
            [
                ['volumes.nope:edit', 'volumes.nope:save'],
                []
            ],
        ];
    }

    public function categoryMutationDataProvider()
    {
        return [
            [
                ['categorygroups.uid:edit', 'categorygroups.uid:delete'],
                ['deleteCategory']
            ],
            [
                ['categorygroups.uid:edit', 'categorygroups.uid:save', 'categorygroups.uid:delete'],
                ['deleteCategory', 'save_someGroup_Category']
            ],
            [
                ['categorygroups.uid:edit', 'categorygroups.uid:save'],
                ['save_someGroup_Category']
            ],
            [
                ['categorygroups.nope:edit', 'categorygroups.nope:save'],
                []
            ],
        ];
    }

    public function tagMutationDataProvider()
    {
        return [
            [
                ['taggroups.uid:edit', 'taggroups.uid:delete'],
                ['deleteTag']
            ],
            [
                ['taggroups.uid:edit', 'taggroups.uid:save', 'taggroups.uid:delete'],
                ['deleteTag', 'save_someGroup_Tag']
            ],
            [
                ['taggroups.uid:edit', 'taggroups.uid:save'],
                ['save_someGroup_Tag']
            ],
            [
                ['taggroups.nope:edit', 'taggroups.nope:save'],
                []
            ],
        ];
    }

    public function entryMutationDataProvider()
    {
        return [
            [
                ['entrytypes.uid:edit', 'entrytypes.uid:delete'],
                ['deleteEntry']
            ],
            [
                ['entrytypes.uid:edit', 'entrytypes.uid:save', 'entrytypes.uid:delete'],
                ['deleteEntry', 'save_news_article_Entry', 'save_news_article_Draft', 'createDraft', 'publishDraft']
            ],
            [
                ['entrytypes.uid:edit', 'entrytypes.uid:create'],
                ['save_news_article_Entry']
            ],
            [
                ['entrytypes.uid:edit', 'entrytypes.uid:save'],
                ['save_news_article_Entry', 'save_news_article_Draft', 'createDraft', 'publishDraft']
            ],
            [
                ['entrytypes.nope:edit', 'entrytypes.nope:save'],
                []
            ],
        ];
    }

    public function globalSetMutationDataProvider()
    {
        return [
            [
                ['globalsets.uid:edit'],
                ['save_gSet_GlobalSet']
            ],
            [
                ['globalsets.uid:edit', 'globalsets.uid2:edit'],
                ['save_gSet_GlobalSet']
            ],
        ];
    }

    /**
     * @param array $scopes
     * @throws \Exception
     */
    private function _mockScope(array $scopes)
    {
        $this->tester->mockCraftMethods('gql', [
            'getActiveSchema' => $this->make(GqlSchema::class, [
                'scope' => $scopes
            ])
        ]);
    }
}
