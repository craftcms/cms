<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\console\controllers;

use Craft;
use craft\console\controllers\EntrifyController;
use craft\elements\Entry;
use craft\services\ProjectConfig;
use craft\test\TestCase;
use crafttests\fixtures\SectionsFixture;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Tests for the `entrify` commands.
 */
class EntrifyControllerTest extends TestCase
{
    public function _fixtures(): array
    {
        return [
            'sections' => [
                'class' => SectionsFixture::class,
            ],
        ];
    }

    /**
     * @see https://github.com/craftcms/cms/issues/20875
     */
    public function testAddSectionToPageKeepsRestrictedCustomSources(): void
    {
        $section = Craft::$app->getEntries()->getSectionByHandle('test1');
        $sourceKey = "section:$section->uid";
        $path = ProjectConfig::PATH_ELEMENT_SOURCES . '.' . Entry::class;
        $projectConfig = Craft::$app->getProjectConfig();

        $projectConfig->set($path, [
            ['type' => 'native', 'key' => '*'],
            ['type' => 'native', 'key' => $sourceKey],
            ['type' => 'custom', 'key' => 'custom:restricted', 'label' => 'Restricted', 'userGroups' => ['group-uid']],
            ['type' => 'custom', 'key' => 'custom:hidden', 'label' => 'Hidden', 'userGroups' => false],
            ['type' => 'custom', 'key' => 'custom:open', 'label' => 'Open'],
        ]);

        // Entrify runs with no logged-in user
        self::assertNull(Craft::$app->getUser()->getIdentity());

        $controller = new EntrifyController('entrify', Craft::$app);
        $controller->section = 'test1';
        (new ReflectionProperty($controller, '_forSingle'))->setValue($controller, false);
        $method = new ReflectionMethod($controller, '_addSectionToPage');
        $method->invoke($controller, 'Categories', 'sitemap');

        $sources = array_column($projectConfig->get($path), null, 'key');
        self::assertSame(['group-uid'], $sources['custom:restricted']['userGroups'] ?? null);
        self::assertFalse($sources['custom:hidden']['userGroups'] ?? null);
        self::assertArrayHasKey('custom:open', $sources);
        self::assertSame('Categories', $sources[$sourceKey]['page']);
        self::assertSame('Entries', $sources['custom:restricted']['page']);
    }
}
