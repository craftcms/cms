<?php

declare(strict_types=1);

use craft\console\controllers\EntrifyController;
use craft\elements\Category;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\fields\Categories as CategoriesField;
use craft\fields\Tags as TagsField;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\TagGroup;
use craft\services\ProjectConfig as LegacyProjectConfig;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserPermission;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->artisan('craft:add-categories-support --force --no-interaction')->assertSuccessful();
    $this->artisan('craft:add-tags-support --force --no-interaction')->assertSuccessful();
    $this->artisan('craft:add-global-sets-support --force --no-interaction')->assertSuccessful();

    // Support tables are created after boot, so register what DeprecatedConcepts registers for an upgraded project.
    app(FieldTypes::class)->register(CategoriesField::class);
    app(FieldTypes::class)->register(TagsField::class);
    app(ProjectConfig::class)
        ->onAdd(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleChangedCategoryGroup($event))
        ->onRemove(LegacyProjectConfig::PATH_CATEGORY_GROUPS . '.{uid}', fn($event) => Craft::$app->getCategories()->handleDeletedCategoryGroup($event))
        ->onAdd(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleChangedTagGroup($event))
        ->onRemove(LegacyProjectConfig::PATH_TAG_GROUPS . '.{uid}', fn($event) => Craft::$app->getTags()->handleDeletedTagGroup($event))
        ->onAdd(LegacyProjectConfig::PATH_GLOBAL_SETS . '.{uid}', fn($event) => Craft::$app->getGlobals()->handleChangedGlobalSet($event))
        ->onRemove(LegacyProjectConfig::PATH_GLOBAL_SETS . '.{uid}', fn($event) => Craft::$app->getGlobals()->handleDeletedGlobalSet($event));

    $this->author = User::factory()->admin()->create(['username' => 'author']);
});

afterEach(function() {
    DeprecatedConcepts::resetSupport();
});

function entrifyController(): EntrifyController
{
    $controller = new EntrifyController('entrify', Craft::$app);
    $controller->interactive = false;

    return $controller;
}

function entrifySummaryLayout(string $type): FieldLayout
{
    $summary = new PlainText(['name' => 'Summary', 'handle' => 'summary']);
    expect(Fields::saveField($summary))->toBeTrue();

    $layout = FieldLayout::make($type);
    $layout->tab('Content', fn(FieldLayoutTab $tab) => $tab->add(CustomField::make($summary)));

    return $layout;
}

function entrifyRelatingEntry(Field $relationField, array $targetIds): Entry
{
    $entry = EntryModel::factory()
        ->withFieldLayout(FieldLayoutModel::factory()->forField($relationField))
        ->createElement();
    $entry->setFieldValue($relationField->handle, $targetIds);
    expect(Elements::saveElement($entry))->toBeTrue();

    return $entry;
}

function entrifyRelatedIds(Entry $entry, string $fieldHandle): array
{
    Fields::refreshFields();

    return Entry::find()->id($entry->id)->one()->getFieldValue($fieldHandle)->status(null)->ids();
}

it('converts a category group into a structure section', function() {
    $group = new CategoryGroup([
        'name' => 'Topics',
        'handle' => 'topics',
        'maxLevels' => 3,
    ]);
    $group->setSiteSettings([
        new CategoryGroup_SiteSettings([
            'siteId' => Sites::getPrimarySite()->id,
            'hasUrls' => true,
            'uriFormat' => 'topics/{slug}',
            'template' => 'topics/_entry',
        ]),
    ]);
    $group->setFieldLayout(entrifySummaryLayout(Category::class));
    expect(Craft::$app->getCategories()->saveGroup($group))->toBeTrue();

    $parent = new Category(['groupId' => $group->id, 'title' => 'Parent']);
    $parent->setFieldValue('summary', 'Parent summary');
    expect(Elements::saveElement($parent))->toBeTrue();
    $child = new Category(['groupId' => $group->id, 'title' => 'Child']);
    expect(Elements::saveElement($child))->toBeTrue()
        ->and(Structures::append($parent->structureId, $child, $parent))->toBeTrue();

    $relationField = new CategoriesField([
        'name' => 'Related Topics',
        'handle' => 'relatedTopics',
        'source' => "group:$group->uid",
    ]);
    expect(Fields::saveField($relationField))->toBeTrue();
    $relatingEntry = entrifyRelatingEntry($relationField, [$child->id]);

    $exitCode = entrifyController()->runAction('categories', [
        'topics',
        'author' => 'author',
    ]);

    expect($exitCode)->toBe(0);

    $section = Sections::getSectionByHandle('topics');
    expect($section)->not->toBeNull()
        ->and($section->type)->toBe(SectionType::Structure)
        ->and($section->maxLevels)->toBe(3)
        ->and($section->getSiteSettings()[Sites::getPrimarySite()->id]->uriFormat)->toBe('topics/{slug}')
        ->and($section->getEntryTypes())->toHaveCount(1)
        ->and($section->getEntryTypes()[0]->getFieldLayout()->getFieldByHandle('summary'))->not->toBeNull();

    $entries = Entry::find()->section('topics')->status(null)->orderBy('lft')->all();
    expect($entries)->toHaveCount(2)
        ->and($entries[0]->id)->toBe($parent->id)
        ->and($entries[0]->getFieldValue('summary'))->toBe('Parent summary')
        ->and($entries[0]->getAuthorId())->toBe($this->author->id)
        ->and($entries[1]->id)->toBe($child->id)
        ->and($entries[1]->getParent()?->id)->toBe($parent->id);

    expect(Craft::$app->getCategories()->getGroupByHandle('topics'))->toBeNull();

    $convertedField = Fields::getFieldByHandle('relatedTopics');
    expect($convertedField)->toBeInstanceOf(EntriesField::class)
        ->and($convertedField->sources)->toBe(["section:$section->uid"])
        ->and(entrifyRelatedIds($relatingEntry, 'relatedTopics'))->toBe([$child->id]);
});

it('converts a tag group into a channel section', function() {
    $group = new TagGroup(['name' => 'Keywords', 'handle' => 'keywords']);
    $group->setFieldLayout(entrifySummaryLayout(Tag::class));
    expect(Craft::$app->getTags()->saveTagGroup($group))->toBeTrue();

    $tag = new Tag(['groupId' => $group->id, 'title' => 'Craft']);
    $tag->setFieldValue('summary', 'Tag summary');
    expect(Elements::saveElement($tag))->toBeTrue();

    $relationField = new TagsField([
        'name' => 'Keywords',
        'handle' => 'keywordTags',
        'source' => "taggroup:$group->uid",
    ]);
    expect(Fields::saveField($relationField))->toBeTrue();
    $relatingEntry = entrifyRelatingEntry($relationField, [$tag->id]);

    $exitCode = entrifyController()->runAction('tags', [
        'keywords',
        'author' => 'author',
    ]);

    expect($exitCode)->toBe(0);

    $section = Sections::getSectionByHandle('keywords');
    expect($section)->not->toBeNull()
        ->and($section->type)->toBe(SectionType::Channel)
        ->and($section->getEntryTypes())->toHaveCount(1)
        ->and($section->getEntryTypes()[0]->handle)->toBe('keyword');

    $entry = Entry::find()->id($tag->id)->status(null)->one();
    expect($entry)->not->toBeNull()
        ->and($entry->sectionId)->toBe($section->id)
        ->and($entry->title)->toBe('Craft')
        ->and($entry->getFieldValue('summary'))->toBe('Tag summary')
        ->and($entry->getAuthorId())->toBe($this->author->id);

    expect(Craft::$app->getTags()->getTagGroupByHandle('keywords'))->toBeNull();

    $convertedField = Fields::getFieldByHandle('keywordTags');
    expect($convertedField)->toBeInstanceOf(EntriesField::class)
        ->and($convertedField->sources)->toBe(["section:$section->uid"])
        ->and(entrifyRelatedIds($relatingEntry, 'keywordTags'))->toBe([$tag->id]);
});

it('converts a global set into a single section', function() {
    $globalSet = new GlobalSet(['name' => 'Site Content', 'handle' => 'siteContent']);
    $globalSet->getBehavior('fieldLayout')->setFieldLayout(entrifySummaryLayout(GlobalSet::class));
    expect(Craft::$app->getGlobals()->saveSet($globalSet))->toBeTrue();

    $globalSet = Craft::$app->getGlobals()->getSetByHandle('siteContent');
    $globalSet->setFieldValue('summary', 'Global summary');
    expect(Elements::saveElement($globalSet))->toBeTrue();

    $editor = User::factory()->create();
    UserPermission::factory()->create(['name' => strtolower("editGlobalSet:{$globalSet->uid}")])
        ->users()->attach($editor->id, ['uid' => (string) Str::uuid()]);

    $exitCode = entrifyController()->runAction('global-set', ['siteContent']);

    expect($exitCode)->toBe(0);

    $section = Sections::getSectionByHandle('siteContent');
    expect($section)->not->toBeNull()
        ->and($section->type)->toBe(SectionType::Single)
        ->and($section->getEntryTypes()[0]->showStatusField)->toBeFalse();

    $entries = Entry::find()->section('siteContent')->status(null)->all();
    expect($entries)->toHaveCount(1)
        ->and($entries[0]->id)->toBe($globalSet->id)
        ->and($entries[0]->title)->toBe('Site Content')
        ->and($entries[0]->getFieldValue('summary'))->toBe('Global summary')
        ->and(UserPermission::where('name', strtolower("saveEntries:$section->uid"))->first()?->users()->pluck('users.id')->all())->toBe([$editor->id]);

    expect(DB::table('globalsets')->where('handle', 'siteContent')->exists())->toBeFalse()
        ->and(app(ProjectConfig::class)->get(LegacyProjectConfig::PATH_GLOBAL_SETS))->toBeEmpty();
});

it('fails without converting anything when the section cannot be created', function() {
    $group = new TagGroup(['name' => 'Keywords', 'handle' => 'keywords']);
    expect(Craft::$app->getTags()->saveTagGroup($group))->toBeTrue();
    $tag = new Tag(['groupId' => $group->id, 'title' => 'Craft']);
    expect(Elements::saveElement($tag))->toBeTrue();

    SectionModel::factory()->create(['handle' => 'keywords']);
    Sections::refreshSections();

    $exitCode = entrifyController()->runAction('tags', [
        'keywords',
        'author' => 'author',
    ]);

    expect($exitCode)->not->toBe(0)
        ->and(EntryTypes::getEntryTypeByHandle('keyword'))->toBeNull()
        ->and(Tag::find()->id($tag->id)->exists())->toBeTrue()
        ->and(Craft::$app->getTags()->getTagGroupByHandle('keywords'))->not->toBeNull();
});
