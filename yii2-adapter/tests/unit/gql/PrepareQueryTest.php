<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\gql\resolvers\elements\User as UserResolver;
use craft\models\GqlSchema;
use craft\records\EntryType;
use craft\records\UserGroup;
use craft\records\Volume;
use craft\services\Entries;
use craft\test\TestCase;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;
use UnitTester;

class PrepareQueryTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    private Volume $_volume;
    private Section $_section;
    private EntryType $_entryType;
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
                        'sections.' . self::SECTION_UID . ':read',
                        'usergroups.' . self::USER_GROUP_UID . ':read',
                    ],
                ]),
            ]
        );

        $this->_setupAssets();
        $this->_setupEntries();
        $this->_setupUsers();
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        $this->_volume->delete();
        Sections::deleteSection($this->_section);
        $this->_entryType->delete();
        $this->_userGroup->delete();

        Craft::$app->set('entries', new Entries());
    }

    public const VOLUME_UID = 'volume-uid--------------------------';
    public const SECTION_UID = 'section-uid-------------------------';
    public const ENTRY_TYPE_UID = 'entryType-uid-----------------------';
    public const USER_GROUP_UID = 'userGroup-uid-----------------------';

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
                AssetResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], fn($result) => $result === ['foo', 'bar'],
            ],
            [
                AssetResolver::class, [null, ['volumeId' => 2, 'folderId' => 5]], fn($result) => $result->volumeId == 2 && $result->folderId == 5,
            ],
            [
                AssetResolver::class, [null, []], fn($result) => $result->where[0] === 'in' && !empty($result->where[2]),
            ],

            // Entries
            [
                EntryResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], fn($result) => $result === ['foo', 'bar'],
            ],
            [
                EntryResolver::class, [null, ['sectionId' => 2, 'typeId' => 5]], fn($result) => $result->sectionId == 2 && $result->typeId == 5,
            ],
            [
                EntryResolver::class, [null, []], function($result) {
                    $section = Sections::getSectionByUid(self::SECTION_UID);
                    return $result->where === ['or', ['in', 'entries.sectionId', [$section->id]]];
                },
            ],

            // Users
            [
                UserResolver::class, [(object)['field' => ['foo', 'bar']], [], 'field'], fn($result) => $result === ['foo', 'bar'],
            ],
            [
                UserResolver::class, [null, []], fn($result) => !empty($result->groupId),
            ],
        ];
    }

    private function _setupAssets()
    {
        $this->_volume = new Volume([
            'uid' => self::VOLUME_UID,
            'name' => Str::random(),
            'handle' => Str::random(),
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

    private function _setupEntries()
    {
        $this->_entryType = new EntryType([
            'uid' => self::ENTRY_TYPE_UID,
            'name' => Str::random(),
            'handle' => Str::random(),
            'hasTitleField' => false,
        ]);
        $this->_entryType->save();

        $this->_section = new Section(
            name: Str::random(),
            handle: Str::random(),
            type: SectionType::Channel,
            enableVersioning: true,
            propagationMethod: PropagationMethod::All,
            uid: self::SECTION_UID,
            siteSettings: [
                1 => new SectionSiteSettings(),
            ],
        );
        Sections::saveSection($this->_section);
        Craft::$app->set('entries', new Entries());

        DB::table(Table::SECTIONS_ENTRYTYPES)->insert([
            'sectionId' => $this->_section->id,
            'typeId' => $this->_entryType->id,
            'sortOrder' => 1,
        ]);
    }

    private function _setupUsers()
    {
        $this->_userGroup = new UserGroup([
            'uid' => self::USER_GROUP_UID,
            'name' => Str::random(),
            'handle' => Str::random(),
        ]);

        $this->_userGroup->save();
    }
}
