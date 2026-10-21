<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\elements\Asset as AssetElement;
use craft\elements\Category as CategoryElement;
use craft\elements\Entry as EntryElement;
use craft\elements\Tag as TagElement;
use craft\elements\User as UserElement;
use craft\enums\Color;
use craft\gql\base\ObjectType;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\queries\Settings as SettingsQuery;
use craft\gql\types\elements\Asset as AssetGqlType;
use craft\gql\types\elements\Category as CategoryGqlType;
use craft\gql\types\elements\Entry as EntryGqlType;
use craft\gql\types\elements\Tag as TagGqlType;
use craft\gql\types\elements\User as UserGqlType;
use craft\gql\types\EntryType as EntryTypeGqlType;
use craft\gql\types\Section as SectionGqlType;
use craft\gql\types\Site as SiteGqlType;
use craft\helpers\Gql as GqlHelper;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\GqlSchema;
use craft\models\Section;
use craft\models\Site;
use craft\models\TagGroup;
use craft\models\UserGroup;
use craft\models\Volume;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\UserFixture;
use crafttests\fixtures\UserGroupsFixture;
use GraphQL\Type\Definition\ResolveInfo;
use UnitTester;

class SettingsComponentsTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
            'assets' => [
                'class' => AssetFixture::class,
            ],
            'userGroups' => [
                'class' => UserGroupsFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class,
            ],
        ];
    }

    protected function _after(): void
    {
        Craft::$app->getGql()->flushCaches();
    }

    /**
     * Test that the settings components are registered alongside the existing element components.
     */
    public function testSchemaComponents(): void
    {
        $this->tester->mockMethods(Craft::$app, 'entries', [
            'getAllSections' => [new Section(['uid' => 'section-uid', 'name' => 'Section', 'type' => Section::TYPE_CHANNEL])],
            'getSectionsByType' => [],
            'getAllEntryTypes' => [new EntryType(['uid' => 'entrytype-uid', 'name' => 'Entry Type'])],
        ]);
        $this->tester->mockMethods(Craft::$app, 'volumes', [
            'getAllVolumes' => [new Volume(['uid' => 'volume-uid', 'name' => 'Volume'])],
        ]);
        $this->tester->mockMethods(Craft::$app, 'userGroups', [
            'getAllGroups' => [new UserGroup(['uid' => 'usergroup-uid', 'name' => 'User Group'])],
        ]);
        $this->tester->mockMethods(Craft::$app, 'categories', [
            'getAllGroups' => [new CategoryGroup(['uid' => 'categorygroup-uid', 'name' => 'Category Group'])],
        ]);
        $this->tester->mockMethods(Craft::$app, 'tags', [
            'getAllTagGroups' => [new TagGroup(['uid' => 'taggroup-uid', 'name' => 'Tag Group'])],
        ]);

        $components = Craft::$app->getGql()->getAllSchemaComponents();
        $queries = array_merge(...array_values($components['queries']));
        $mutations = array_merge(...array_values($components['mutations']));

        foreach (['sites', 'sections', 'entrytypes', 'volumes', 'usergroups', 'categorygroups', 'taggroups'] as $type) {
            self::assertArrayHasKey("settings.$type:read", $queries);
        }

        foreach (array_keys($mutations) as $component) {
            self::assertStringStartsNotWith('settings.', $component);
        }
    }

    /**
     * Test that the settings fields are only available when their component is included in the schema.
     *
     * @dataProvider conditionalFieldDataProvider
     * @param callable $getFields
     * @param string $fieldName
     * @param string $component
     */
    public function testConditionalFields(callable $getFields, string $fieldName, string $component): void
    {
        $gql = Craft::$app->getGql();

        $gql->flushCaches();
        $gql->setActiveSchema(new GqlSchema(['scope' => ['usergroups.everyone:read']]));
        self::assertArrayNotHasKey($fieldName, $getFields());

        $gql->flushCaches();
        $gql->setActiveSchema(new GqlSchema(['scope' => ['usergroups.everyone:read', "$component:read"]]));
        self::assertArrayHasKey($fieldName, $getFields());
    }

    /**
     * Test resolving the settings fields on elements and settings types.
     *
     * @dataProvider resolverDataProvider
     * @param string $gqlTypeClass
     * @param callable $getSource
     * @param string $fieldName
     * @param callable $expected
     */
    public function testResolvers(string $gqlTypeClass, callable $getSource, string $fieldName, callable $expected): void
    {
        $source = $getSource($this);
        /** @var ObjectType $type */
        $type = $this->make($gqlTypeClass);
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => $fieldName]);

        self::assertSame($expected($source), $type->resolveWithDirectives($source, [], null, $resolveInfo));
    }

    /**
     * Test that a user’s groups are limited to the groups the schema can query users in.
     */
    public function testUserGroupsAreScoped(): void
    {
        $group1 = new UserGroup(['uid' => 'group-1-uid', 'handle' => 'group1']);
        $group2 = new UserGroup(['uid' => 'group-2-uid', 'handle' => 'group2']);
        $user = $this->make(UserElement::class, [
            'getGroups' => [$group1, $group2],
        ]);
        $resolveInfo = $this->make(ResolveInfo::class, ['fieldName' => 'groups']);
        $type = $this->make(UserGqlType::class);
        $gql = Craft::$app->getGql();

        $gql->setActiveSchema(new GqlSchema(['scope' => ['settings.usergroups:read', 'usergroups.group-1-uid:read']]));
        self::assertSame([$group1], $type->resolveWithDirectives($user, [], null, $resolveInfo));

        $gql->setActiveSchema(new GqlSchema(['scope' => ['settings.usergroups:read', 'usergroups.everyone:read']]));
        self::assertSame([$group1, $group2], $type->resolveWithDirectives($user, [], null, $resolveInfo));
    }

    /**
     * Test that a full schema validates and can be queried for the settings fields.
     */
    public function testQueryingSettingsFields(): void
    {
        $gql = Craft::$app->getGql();
        $schema = GqlHelper::createFullAccessSchema();
        $gql->flushCaches();
        $gql->getSchemaDef($schema, true);

        $result = $gql->executeQuery($schema, <<<GQL
{
  entries(section: "test1", limit: 1) {
    ... on EntryInterface {
      site { handle language baseUrl }
      section { handle type entryTypes { handle } }
      type { handle color }
    }
  }
  assets(limit: 1) {
    ... on AssetInterface { volume { handle } }
  }
  users(limit: 1) {
    ... on UserInterface { groups { handle } }
  }
}
GQL, debugMode: true);

        self::assertArrayNotHasKey('errors', $result, json_encode($result['errors'] ?? null));
        self::assertSame('test1', $result['data']['entries'][0]['section']['handle'] ?? null);
        self::assertSame('channel', $result['data']['entries'][0]['section']['type'] ?? null);
        self::assertNotEmpty($result['data']['entries'][0]['section']['entryTypes'] ?? null);
        self::assertNotEmpty($result['data']['entries'][0]['type']['handle'] ?? null);
        self::assertNotEmpty($result['data']['entries'][0]['site']['handle'] ?? null);
        self::assertNotEmpty($result['data']['assets'][0]['volume']['handle'] ?? null);
        self::assertIsArray($result['data']['users'][0]['groups'] ?? null);
    }

    /**
     * Test the top-level settings queries.
     */
    public function testTopLevelQueries(): void
    {
        $gql = Craft::$app->getGql();
        $schema = GqlHelper::createFullAccessSchema();
        // Category and tag groups don't have fixtures, so their components wouldn't be offered
        $schema->scope[] = 'settings.categorygroups:read';
        $schema->scope[] = 'settings.taggroups:read';
        $gql->flushCaches();

        $result = $gql->executeQuery($schema, <<<GQL
{
  sites { handle }
  site(handle: "testSite1") { handle }
  sections(handle: "test1") { handle }
  section(handle: "missing") { handle }
  entryTypes { handle }
  volumes(handle: ["testVolume1", "testVolume2", "testVolume4"]) { handle }
  userGroups(handle: ["group1", "group2"]) { handle }
  userGroup(uid: "usergroup-1000-------------------uid") { handle }
  categoryGroups { handle }
  tagGroups { handle }
}
GQL, debugMode: true);

        self::assertArrayNotHasKey('errors', $result, json_encode($result['errors'] ?? null));
        $data = $result['data'];
        self::assertContains('testSite1', array_column($data['sites'], 'handle'));
        self::assertSame(['handle' => 'testSite1'], $data['site']);
        self::assertSame([['handle' => 'test1']], $data['sections']);
        self::assertNull($data['section']);
        self::assertNotEmpty($data['entryTypes']);
        self::assertSame(['testVolume1', 'testVolume4'], array_column($data['volumes'], 'handle')); // testVolume2 is soft-deleted
        self::assertSame(['group1', 'group2'], array_column($data['userGroups'], 'handle'));
        self::assertSame(['handle' => 'group1'], $data['userGroup']);
        self::assertSame([], $data['categoryGroups']);
        self::assertSame([], $data['tagGroups']);
    }

    /**
     * Test that the top-level settings queries are only available when their component is included in the schema.
     */
    public function testTopLevelQueriesRequireComponents(): void
    {
        $gql = Craft::$app->getGql();
        $queryNames = [
            'sites' => ['sites', 'site'],
            'sections' => ['sections', 'section'],
            'entrytypes' => ['entryTypes', 'entryType'],
            'volumes' => ['volumes', 'volume'],
            'usergroups' => ['userGroups', 'userGroup'],
            'categorygroups' => ['categoryGroups', 'categoryGroup'],
            'taggroups' => ['tagGroups', 'tagGroup'],
        ];

        $gql->setActiveSchema(new GqlSchema(['scope' => ['usergroups.everyone:read']]));
        self::assertSame([], SettingsQuery::getQueries());

        foreach ($queryNames as $component => $names) {
            $gql->setActiveSchema(new GqlSchema(['scope' => ["settings.$component:read"]]));
            self::assertSame($names, array_keys(SettingsQuery::getQueries()));
        }
    }

    public static function conditionalFieldDataProvider(): array
    {
        return [
            [fn() => EntryInterface::getFieldDefinitions(), 'site', 'settings.sites'],
            [fn() => EntryInterface::getFieldDefinitions(), 'section', 'settings.sections'],
            [fn() => EntryInterface::getFieldDefinitions(), 'type', 'settings.entrytypes'],
            [fn() => AssetInterface::getFieldDefinitions(), 'volume', 'settings.volumes'],
            [fn() => UserInterface::getFieldDefinitions(), 'groups', 'settings.usergroups'],
            [fn() => CategoryInterface::getFieldDefinitions(), 'group', 'settings.categorygroups'],
            [fn() => TagInterface::getFieldDefinitions(), 'group', 'settings.taggroups'],
            [fn() => SectionGqlType::getFieldDefinitions(), 'entryTypes', 'settings.entrytypes'],
        ];
    }

    public static function resolverDataProvider(): array
    {
        $section = new Section(['handle' => 'news']);
        $entryType = new EntryType(['handle' => 'article', 'color' => Color::Red]);
        $volume = new Volume(['handle' => 'images']);
        $categoryGroup = new CategoryGroup(['handle' => 'topics']);
        $tagGroup = new TagGroup(['handle' => 'keywords']);
        $site = new Site(['handle' => 'default', 'baseUrl' => 'https://example.test/']);

        return [
            [EntryGqlType::class, fn(self $test) => $test->make(EntryElement::class, ['getSection' => $section]), 'section', fn() => $section],
            [EntryGqlType::class, fn(self $test) => $test->make(EntryElement::class, ['getType' => $entryType]), 'type', fn() => $entryType],
            [EntryGqlType::class, fn(self $test) => $test->make(EntryElement::class, ['getSite' => $site]), 'site', fn() => $site],
            [AssetGqlType::class, fn(self $test) => $test->make(AssetElement::class, ['getVolume' => $volume]), 'volume', fn() => $volume],
            [CategoryGqlType::class, fn(self $test) => $test->make(CategoryElement::class, ['getGroup' => $categoryGroup]), 'group', fn() => $categoryGroup],
            [TagGqlType::class, fn(self $test) => $test->make(TagElement::class, ['getGroup' => $tagGroup]), 'group', fn() => $tagGroup],
            [SectionGqlType::class, fn(self $test) => $test->make(Section::class, ['getEntryTypes' => [$entryType]]), 'entryTypes', fn() => [$entryType]],
            [SectionGqlType::class, fn() => $section, 'handle', fn() => 'news'],
            [EntryTypeGqlType::class, fn() => $entryType, 'color', fn() => 'red'],
            [SiteGqlType::class, fn() => $site, 'baseUrl', fn() => 'https://example.test/'],
        ];
    }
}
