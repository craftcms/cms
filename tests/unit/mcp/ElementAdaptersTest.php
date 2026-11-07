<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\mcp\capabilities\Elements;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\TagGroup;
use craft\test\TestCase;
use crafttests\fixtures\GlobalSetFixture;
use Mcp\Exception\ToolCallException;

/** Categories, tags, and global sets through the elements_* tools. */
class ElementAdaptersTest extends TestCase
{
    private ?CategoryGroup $categoryGroup = null;
    private ?TagGroup $tagGroup = null;

    public function _fixtures(): array
    {
        return ['globalSets' => ['class' => GlobalSetFixture::class]];
    }

    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
    }

    protected function _after(): void
    {
        $this->categoryGroup && Craft::$app->getCategories()->deleteGroup($this->categoryGroup);
        $this->tagGroup && Craft::$app->getTags()->deleteTagGroup($this->tagGroup);
        Craft::$app->getUser()->setIdentity(null);
    }

    public function testCategoriesAndTagsCanBeCreatedListedAndRead(): void
    {
        $this->categoryGroup = new CategoryGroup(['name' => 'MCP Topics', 'handle' => 'mcpTopics']);
        $siteId = Craft::$app->getSites()->getPrimarySite()->id;
        $this->categoryGroup->setSiteSettings([$siteId => new CategoryGroup_SiteSettings(['siteId' => $siteId, 'hasUrls' => false])]);
        self::assertTrue(Craft::$app->getCategories()->saveGroup($this->categoryGroup));
        $this->tagGroup = new TagGroup(['name' => 'MCP Tags', 'handle' => 'mcpTags']);
        self::assertTrue(Craft::$app->getTags()->saveTagGroup($this->tagGroup));

        $category = $this->elements()->create('craft\elements\Category', ['groupId' => $this->categoryGroup->id, 'title' => 'Releases'])['element'];
        $this->elements()->create('tags', ['groupId' => $this->tagGroup->id, 'title' => 'craft6']);

        $categories = $this->elements()->list('categories', ['group' => 'mcpTopics'])->structuredContent['elements'];
        self::assertSame([$category['id']], array_column($categories, 'id'));
        self::assertSame('Releases', $this->elements()->get('categories', $category['id'])['element']['title']);
        self::assertSame(['craft6'], array_column($this->elements()->list('tags', ['groupId' => $this->tagGroup->id])->structuredContent['elements'], 'title'));
    }

    public function testGlobalSetsRequirePermissionToEdit(): void
    {
        $set = GlobalSet::find()->handle('aGlobalSet')->one();
        $deleted = GlobalSet::find()->handle('aDeletedGlobalSet')->trashed()->one();
        $user = new User(['username' => 'mcp-globals', 'email' => 'mcp-globals@example.com', 'active' => true]);
        self::assertTrue(Craft::$app->getElements()->saveElement($user, false));
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accesscp']);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->one());

        // Global sets’ canSave() allows everyone, but the control panel requires permission to edit each set
        $this->assertFails(fn() => $this->elements()->update('globalSets', $set->id, fields: ['plainTextField3' => 'Changed']));
        $this->assertFails(fn() => $this->elements()->get('globalSets', $set->id));

        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accesscp', "editglobalset:$set->uid"]);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->one());
        $this->elements()->update('globalSets', $set->id, fields: ['plainTextField3' => 'Changed']);
        self::assertSame('Changed', GlobalSet::find()->id($set->id)->one()->getFieldValue('plainTextField3'));

        // Deleted global sets come back through project config, not by restoring their elements
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $this->assertFails(fn() => $this->elements()->restore('globalSets', $deleted->id));
    }

    private function assertFails(callable $callback): void
    {
        $failed = false;

        try {
            $callback();
        } catch (ToolCallException) {
            $failed = true;
        }

        self::assertTrue($failed, 'Expected the tool call to fail.');
    }

    private function elements(): Elements
    {
        return Craft::$app->get('mcp')->getCapabilityContainer()->get(Elements::class);
    }
}
