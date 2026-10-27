<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql\mutations;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\enums\PropagationMethod;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fs\Local;
use craft\gql\resolvers\mutations\Asset as AssetMutationResolver;
use craft\gql\resolvers\mutations\Category as CategoryMutationResolver;
use craft\gql\resolvers\mutations\Entry as EntryMutationResolver;
use craft\gql\resolvers\mutations\Tag as TagMutationResolver;
use craft\helpers\FileHelper;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\GqlSchema;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\models\TagGroup;
use craft\models\Volume;
use craft\test\TestCase;
use crafttests\fixtures\SitesFixture;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use UnitTester;

/**
 * Tests that GraphQL mutations respect the schema’s allowed sites.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class SiteScopedMutationsTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    private Site $siteA;
    private Site $siteB;
    private Site $siteC;
    private EntryType $entryType;
    private Section $section;
    private CategoryGroup $categoryGroup;
    private TagGroup $tagGroup;
    private Local $fs;
    private Volume $volume;
    private string $fsPath;

    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class,
            ],
        ];
    }

    /**
     * @param 'entry'|'category'|'tag'|'asset' $type
     * @dataProvider deleteMutationDataProvider
     */
    public function testDeleteRequiresAllElementSites(string $type): void
    {
        $element = $this->createElement($type);

        $siteCount = $this->siteCount($element);

        $this->setSchema([$this->siteA], $type, 'delete');
        $this->expectError(fn() => $this->delete($type, $element->id));
        self::assertSame($siteCount, $this->siteCount($element), 'The element still exists in all its sites.');

        $this->setSchema(Craft::$app->getSites()->getAllSites(), $type, 'delete');
        self::assertTrue($this->delete($type, $element->id));
        self::assertSame(0, $this->siteCount($element), 'The element was deleted.');
    }

    public static function deleteMutationDataProvider(): array
    {
        return [
            'entry' => ['entry'],
            'category' => ['category'],
            'tag' => ['tag'],
            'asset' => ['asset'],
        ];
    }

    public function testDeleteEntryWithSiteIdRequiresAllElementSites(): void
    {
        $entry = $this->createElement('entry');

        $this->setSchema([$this->siteA], 'entry', 'delete');
        $this->expectError(fn() => (new EntryMutationResolver())->deleteEntry(null, [
            'id' => $entry->id,
            'siteId' => $this->siteA->id,
        ], null, $this->make(ResolveInfo::class)));
        self::assertSame(2, $this->siteCount($entry), 'The entry still exists in both sites.');
    }

    public function testPublishDraftRequiresAllElementSites(): void
    {
        $entry = $this->createElement('entry');
        $draft = Craft::$app->getDrafts()->createDraft($entry);

        /** @var Entry $siteADraft */
        $siteADraft = Entry::find()->draftId($draft->draftId)->siteId($this->siteA->id)->status(null)->one();
        $siteADraft->title = 'Changed';
        self::assertTrue(Craft::$app->getElements()->saveElement($siteADraft));

        $this->setSchema([$this->siteB], 'entry', 'save', ['elements.drafts:read']);
        $this->expectError(fn() => (new EntryMutationResolver())->publishDraft(null, [
            'id' => $draft->draftId,
        ], null, $this->make(ResolveInfo::class)));
        self::assertSame('Original', $this->entryTitle($entry, $this->siteA));

        $this->setSchema(Craft::$app->getSites()->getAllSites(), 'entry', 'save', ['elements.drafts:read']);
        (new EntryMutationResolver())->publishDraft(null, [
            'id' => $draft->draftId,
        ], null, $this->make(ResolveInfo::class));
        self::assertSame('Changed', $this->entryTitle($entry, $this->siteA));
    }

    public function testCreateDraftRequiresAllowedSite(): void
    {
        $entry = $this->createElement('entry');

        $this->setSchema([$this->siteC], 'entry', 'save');
        $this->expectError(fn() => (new EntryMutationResolver())->createDraft(null, [
            'id' => $entry->id,
        ], null, $this->make(ResolveInfo::class)));
        self::assertFalse(Entry::find()->draftOf($entry)->site('*')->status(null)->exists());

        $this->setSchema([$this->siteB], 'entry', 'save');
        $draftId = (new EntryMutationResolver())->createDraft(null, [
            'id' => $entry->id,
        ], null, $this->make(ResolveInfo::class));
        self::assertNotNull($draftId);
    }

    /**
     * @param 'entry'|'category'|'tag'|'asset' $type
     */
    private function createElement(string $type): ElementInterface
    {
        $element = match ($type) {
            'entry' => new Entry([
                'sectionId' => $this->section->id,
                'typeId' => $this->entryType->id,
                'title' => 'Original',
            ]),
            'category' => new Category([
                'groupId' => $this->categoryGroup->id,
                'title' => 'Original',
            ]),
            'tag' => new Tag([
                'groupId' => $this->tagGroup->id,
                'title' => 'Original',
            ]),
            'asset' => $this->newAsset(),
        };
        $element->siteId = $this->siteA->id;

        self::assertTrue(Craft::$app->getElements()->saveElement($element), (string)json_encode($element->getErrors()));
        self::assertGreaterThanOrEqual(2, $this->siteCount($element), "The $type exists in multiple sites.");

        return $element;
    }

    private function newAsset(): Asset
    {
        $tempPath = Craft::$app->getPath()->getTempPath() . '/site-scoped-mutation.txt';
        file_put_contents($tempPath, 'test');

        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->setFilename('site-scoped-mutation.txt');
        $asset->newFolderId = Craft::$app->getAssets()->getRootFolderByVolumeId($this->volume->id)->id;
        $asset->setVolumeId($this->volume->id);
        $asset->setScenario(Asset::SCENARIO_CREATE);
        return $asset;
    }

    /**
     * @param 'entry'|'category'|'tag'|'asset' $type
     */
    private function delete(string $type, int $id): bool
    {
        $arguments = ['id' => $id];
        $resolveInfo = $this->make(ResolveInfo::class);

        return match ($type) {
            'entry' => (new EntryMutationResolver())->deleteEntry(null, $arguments, null, $resolveInfo),
            'category' => (new CategoryMutationResolver())->deleteCategory(null, $arguments, null, $resolveInfo),
            'tag' => (new TagMutationResolver())->deleteTag(null, $arguments, null, $resolveInfo),
            'asset' => (new AssetMutationResolver())->deleteAsset(null, $arguments, null, $resolveInfo),
        };
    }

    /**
     * @param Site[] $sites
     * @param 'entry'|'category'|'tag'|'asset' $type
     */
    private function setSchema(array $sites, string $type, string $action, array $extraScope = []): void
    {
        $scope = match ($type) {
            'entry' => ["sections.{$this->section->uid}:read", "sections.{$this->section->uid}:$action"],
            'category' => ["categorygroups.{$this->categoryGroup->uid}:read", "categorygroups.{$this->categoryGroup->uid}:$action"],
            'tag' => ["taggroups.{$this->tagGroup->uid}:read", "taggroups.{$this->tagGroup->uid}:$action"],
            'asset' => ["volumes.{$this->volume->uid}:read", "volumes.{$this->volume->uid}:$action"],
        };

        foreach ($sites as $site) {
            $scope[] = "sites.$site->uid:read";
        }

        $this->tester->mockCraftMethods('gql', [
            'getActiveSchema' => new GqlSchema([
                'name' => 'Test schema',
                'scope' => array_merge($scope, $extraScope),
            ]),
        ]);
    }

    private function expectError(callable $callback): void
    {
        try {
            $callback();
        } catch (Error $e) {
            self::assertSame('Unable to perform the action.', $e->getMessage());
            return;
        }

        self::fail('The mutation was not refused.');
    }

    private function siteCount(ElementInterface $element): int
    {
        return (int)$element::find()
            ->id($element->id)
            ->site('*')
            ->status(null)
            ->count();
    }

    private function entryTitle(ElementInterface $entry, Site $site): ?string
    {
        return Entry::find()->id($entry->id)->siteId($site->id)->status(null)->one()?->title;
    }

    protected function _before(): void
    {
        parent::_before();

        $sitesService = Craft::$app->getSites();
        $this->siteA = $sitesService->getPrimarySite();
        $this->siteB = $sitesService->getSiteByHandle('testSite1');
        $this->siteC = $sitesService->getSiteByHandle('testSite2');

        $this->entryType = new EntryType([
            'name' => 'Site Scope Test',
            'handle' => 'siteScopeTest',
        ]);
        $fieldLayout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements([new EntryTitleField()]);
        $fieldLayout->setTabs([$tab]);
        $this->entryType->setFieldLayout($fieldLayout);
        self::assertTrue(Craft::$app->getEntries()->saveEntryType($this->entryType));

        $this->section = new Section([
            'name' => 'Site Scope Test',
            'handle' => 'siteScopeTest',
            'type' => Section::TYPE_CHANNEL,
            'propagationMethod' => PropagationMethod::All,
        ]);
        $this->section->setEntryTypes([$this->entryType]);
        $this->section->setSiteSettings([
            $this->siteA->id => new Section_SiteSettings(['siteId' => $this->siteA->id, 'hasUrls' => false]),
            $this->siteB->id => new Section_SiteSettings(['siteId' => $this->siteB->id, 'hasUrls' => false]),
        ]);
        self::assertTrue(Craft::$app->getEntries()->saveSection($this->section), (string)json_encode($this->section->getErrors()));

        $this->categoryGroup = new CategoryGroup([
            'name' => 'Site Scope Test',
            'handle' => 'siteScopeTest',
        ]);
        $categorySiteSettings = [];
        foreach ($sitesService->getAllSiteIds() as $siteId) {
            $categorySiteSettings[$siteId] = new CategoryGroup_SiteSettings(['siteId' => $siteId, 'hasUrls' => false]);
        }
        $this->categoryGroup->setSiteSettings($categorySiteSettings);
        self::assertTrue(Craft::$app->getCategories()->saveGroup($this->categoryGroup), (string)json_encode($this->categoryGroup->getErrors()));

        $this->tagGroup = new TagGroup([
            'name' => 'Site Scope Test',
            'handle' => 'siteScopeTest',
        ]);
        self::assertTrue(Craft::$app->getTags()->saveTagGroup($this->tagGroup));

        $this->fsPath = sys_get_temp_dir() . '/craft-site-scope-test-fs';
        FileHelper::createDirectory($this->fsPath);
        $this->fs = new Local([
            'name' => 'Site Scope Test',
            'handle' => 'siteScopeTest',
            'path' => $this->fsPath,
        ]);
        self::assertTrue(Craft::$app->getFs()->saveFilesystem($this->fs), (string)json_encode($this->fs->getErrors()));

        $this->volume = new Volume([
            'name' => 'Site Scope Test',
            'handle' => 'siteScopeTest',
            'fsHandle' => 'siteScopeTest',
        ]);
        self::assertTrue(Craft::$app->getVolumes()->saveVolume($this->volume), (string)json_encode($this->volume->getErrors()));
    }

    protected function _after(): void
    {
        Craft::$app->getVolumes()->deleteVolume($this->volume);
        Craft::$app->getFs()->removeFilesystem($this->fs);
        FileHelper::removeDirectory($this->fsPath);
        Craft::$app->getTags()->deleteTagGroup($this->tagGroup);
        Craft::$app->getCategories()->deleteGroup($this->categoryGroup);
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->entryType);

        parent::_after();
    }
}
