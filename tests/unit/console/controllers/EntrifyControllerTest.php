<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\console\controllers;

use Craft;
use craft\console\controllers\SectionsController;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\EntryType;
use craft\models\TagGroup;
use craft\services\ProjectConfig;
use craft\test\console\ConsoleTest;
use craft\test\TestSetup;
use yii\console\ExitCode;

/**
 * Tests for the `entrify/categories` and `entrify/tags` commands.
 *
 * @see https://github.com/craftcms/cms/issues/20875
 */
class EntrifyControllerTest extends ConsoleTest
{
    private const SOURCES_PATH = ProjectConfig::PATH_ELEMENT_SOURCES . '.' . Entry::class;

    private EntryType $entryType;

    protected function _before(): void
    {
        parent::_before();

        // Element sources are memoized statically, so forget the ones from earlier tests
        Craft::$app->getElementSources()->refreshSources();

        $this->entryType = new EntryType([
            'name' => 'Entrify Test',
            'handle' => 'entrifyTest',
        ]);
        self::assertTrue(Craft::$app->getEntries()->saveEntryType($this->entryType));

        // `sections/create` only adds an entry type to the new section when it's run interactively,
        // so give it one up front
        Craft::$app->controllerMap['sections'] = [
            'class' => SectionsController::class,
            'entryTypes' => $this->entryType->handle,
        ];

        Craft::$app->getProjectConfig()->set(self::SOURCES_PATH, [
            ['type' => 'native', 'key' => '*'],
            ['type' => 'custom', 'key' => 'custom:restricted', 'label' => 'Restricted', 'userGroups' => ['group-uid']],
            ['type' => 'custom', 'key' => 'custom:hidden', 'label' => 'Hidden', 'userGroups' => false],
            ['type' => 'custom', 'key' => 'custom:open', 'label' => 'Open'],
        ]);
    }

    protected function _after(): void
    {
        $entriesService = Craft::$app->getEntries();
        $section = $entriesService->getSectionByHandle('entrifyTest');
        if ($section) {
            $entriesService->deleteSection($section);
        }
        $entriesService->deleteEntryType($this->entryType);
        Craft::$app->getProjectConfig()->remove(self::SOURCES_PATH);

        parent::_after();
    }

    public function testCategoriesKeepsRestrictedEntrySources(): void
    {
        $group = new CategoryGroup([
            'name' => 'Entrify Test',
            'handle' => 'entrifyTest',
        ]);
        $group->setSiteSettings(array_map(
            fn(int $siteId) => new CategoryGroup_SiteSettings(['siteId' => $siteId, 'hasUrls' => false]),
            Craft::$app->getSites()->getAllSiteIds(),
        ));
        self::assertTrue(Craft::$app->getCategories()->saveGroup($group));
        $category = new Category(['groupId' => $group->id, 'title' => 'Category']);
        self::assertTrue(Craft::$app->getElements()->saveElement($category));

        $this->consoleCommand('entrify/categories', [
            $group->handle,
            'author' => TestSetup::USERNAME,
            'interactive' => false,
        ], true)
            ->confirm('Delete the “Entrify Test” category group?', true, true)
            ->exitCode(ExitCode::OK)
            ->run();

        $this->assertEntrySources('Categories', $category->id);
    }

    public function testTagsKeepsRestrictedEntrySources(): void
    {
        $group = new TagGroup([
            'name' => 'Entrify Test',
            'handle' => 'entrifyTest',
        ]);
        self::assertTrue(Craft::$app->getTags()->saveTagGroup($group));
        $tag = new Tag(['groupId' => $group->id, 'title' => 'Tag']);
        self::assertTrue(Craft::$app->getElements()->saveElement($tag));

        $this->consoleCommand('entrify/tags', [
            $group->handle,
            'author' => TestSetup::USERNAME,
            'interactive' => false,
        ], true)
            ->confirm('Delete the “Entrify Test” tag group?', true, true)
            ->exitCode(ExitCode::OK)
            ->run();

        $this->assertEntrySources('Tags', $tag->id);
    }

    private function assertEntrySources(string $page, int $elementId): void
    {
        // The command created the section and converted the element
        $section = Craft::$app->getEntries()->getSectionByHandle('entrifyTest');
        self::assertNotNull($section);
        self::assertNotNull(Entry::find()->id($elementId)->sectionId($section->id)->status(null)->one());

        $sources = array_column(Craft::$app->getProjectConfig()->get(self::SOURCES_PATH), null, 'key');
        self::assertSame(['group-uid'], $sources['custom:restricted']['userGroups'] ?? null);
        self::assertFalse($sources['custom:hidden']['userGroups'] ?? null);
        self::assertArrayHasKey('custom:open', $sources);
        self::assertSame('Entries', $sources['custom:restricted']['page']);
        self::assertSame($page, $sources["section:$section->uid"]['page'] ?? null);
    }
}
