<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\DeletionBlocker\BlockerRegistry;
use CraftCms\Cms\DeletionBlocker\EntryTypeUsageBlocker;
use CraftCms\Cms\DeletionBlocker\FieldUsageBlocker;
use CraftCms\Cms\DeletionBlocker\SourceUsageBlocker;
use CraftCms\Cms\DeletionBlocker\SubjectTypeRegistry;
use CraftCms\Cms\DeletionBlocker\TrashedElementsBlocker;
use CraftCms\Cms\DeletionBlocker\UserGroupUsageBlocker;
use CraftCms\Cms\Element\DeletionBlockers\Contracts\DeletionBlockerInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\DeletionBlockersController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

/**
 * @param  DeletionBlockerInterface[]  $blockers
 * @return list<DeletionBlockerInterface>
 */
function activeDeletionBlockers(array $blockers): array
{
    return array_values(array_filter($blockers, fn (DeletionBlockerInterface $blocker) => $blocker->isActive()));
}

/**
 * @template T of DeletionBlockerInterface
 *
 * @param  class-string<T>  $class
 * @return T
 */
function activeDeletionBlocker(string $type, mixed $subject, string $class): DeletionBlockerInterface
{
    $matches = array_values(array_filter(
        activeDeletionBlockers(app(BlockerRegistry::class)->blockers($type, $subject)),
        fn ($blocker) => $blocker instanceof $class,
    ));

    expect($matches)->toHaveCount(1);

    return $matches[0];
}

function deletionBlockerEntry(SectionModel $section, EntryTypeModel $entryType, ?string $summary = null): Entry
{
    $entry = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement();

    if ($summary !== null) {
        $entry->setFieldValue('summary', $summary);
    }

    expect(Elements::saveElement($entry))->toBeTrue();

    return $entry;
}

it('counts canonical elements with a value for the field in each layout', function () {
    $field = Fields::createField(['type' => PlainText::class, 'name' => 'Summary', 'handle' => 'summary']);
    Fields::saveField($field);

    $article = EntryTypeModel::factory()->withFieldLayout(FieldLayout::factory()->forField($field))->create(['name' => 'Article']);
    $page = EntryTypeModel::factory()->withFieldLayout(FieldLayout::factory()->forField($field))->create(['name' => 'Page']);
    $section = SectionModel::factory()->withEntryTypes($article, $page)->create();
    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    deletionBlockerEntry($section, $article, 'First');
    $second = deletionBlockerEntry($section, $article, 'Second');
    deletionBlockerEntry($section, $article);
    Elements::deleteElement(deletionBlockerEntry($section, $article, 'Trashed'));

    $emptyPage = deletionBlockerEntry($section, $page);
    $draft = app(Drafts::class)->createDraft($emptyPage, User::findOne()->id);
    $draft->setFieldValue('summary', 'Draft value');
    Elements::saveElement($draft);
    app(Drafts::class)->createDraft($second, User::findOne()->id);

    $blocker = activeDeletionBlocker('field', Fields::getFieldById($field->id), FieldUsageBlocker::class);

    expect(array_column($blocker->getItems(), 'count', 'label'))->toBe(['Article' => 2, 'Page' => 0])
        ->and($blocker->getItems()[0]['description'])->toBe('2 entries have a value.')
        ->and($blocker->getSummary())->toBe('The field is included in 2 field layouts.');
});

it('has no active blockers for an unused field', function () {
    $field = Fields::createField(['type' => PlainText::class, 'name' => 'Unused', 'handle' => 'unused']);
    Fields::saveField($field);

    expect(activeDeletionBlockers(app(BlockerRegistry::class)->blockers('field', $field)))->toBe([]);
});

it('lists the sections that use an entry type and the entries the deletion trashes', function () {
    $entryType = EntryTypeModel::factory()->create();
    $blog = SectionModel::factory()->withEntryTypes($entryType)->create(['name' => 'Blog']);
    $pages = SectionModel::factory()->withEntryTypes($entryType)->create(['name' => 'Pages']);

    EntryModel::factory()->forSection($blog)->forEntryType($entryType)->createElement();
    EntryModel::factory()->count(2)->forSection($pages)->forEntryType($entryType)->create();
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $subject = EntryTypes::getEntryTypeById($entryType->id);
    $usage = activeDeletionBlocker('entry-type', $subject, EntryTypeUsageBlocker::class);

    expect(array_column($usage->getItems(), 'count', 'label'))->toBe(['Blog' => 1, 'Pages' => 2])
        ->and($usage->getSummary())->toBe('The entry type is used by 2 sections.')
        ->and(activeDeletionBlocker('entry-type', $subject, TrashedElementsBlocker::class)->getSummary())
        ->toBe('3 entries use the entry type.');
});

it('lists the relation fields that offer a section and the entries the deletion trashes', function () {
    $entryType = EntryTypeModel::factory()->create();
    $blog = SectionModel::factory()->withEntryTypes($entryType)->create(['name' => 'Blog']);
    $other = SectionModel::factory()->withEntryTypes($entryType)->create(['name' => 'Other']);

    $related = Fields::createField(['type' => EntriesField::class, 'name' => 'Related', 'handle' => 'related', 'sources' => ["section:$blog->uid"]]);
    Fields::saveField($related);
    $anything = Fields::createField(['type' => EntriesField::class, 'name' => 'Anything', 'handle' => 'anything', 'sources' => '*']);
    Fields::saveField($anything);
    $elsewhere = Fields::createField(['type' => EntriesField::class, 'name' => 'Elsewhere', 'handle' => 'elsewhere', 'sources' => ["section:$other->uid"]]);
    Fields::saveField($elsewhere);

    $target = EntryModel::factory()->forSection($blog)->forEntryType($entryType)->createElement();
    $otherTarget = EntryModel::factory()->forSection($other)->forEntryType($entryType)->createElement();
    $sources = EntryModel::factory()->count(3)->forSection($other)->forEntryType($entryType)->create();

    foreach ([[$sources[0], $target], [$sources[1], $target], [$sources[1], $target], [$sources[2], $otherTarget]] as $i => [$source, $relatedTarget]) {
        DB::table(Table::RELATIONS)->insert([
            'fieldId' => $related->id,
            'sourceId' => $source->id,
            'targetId' => $relatedTarget->id,
            'sortOrder' => $i + 1,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => fake()->uuid(),
        ]);
    }
    Sections::refreshSections();

    $subject = Sections::getSectionById($blog->id);
    $usage = activeDeletionBlocker('section', $subject, SourceUsageBlocker::class);
    $counts = array_column($usage->getItems(), 'count', 'label');
    ksort($counts);

    expect($counts)->toBe(['Anything' => 0, 'Related' => 2])
        ->and(array_column($usage->getItems(), 'description', 'label')['Related'])->toBe('2 elements relate to Blog entries.')
        ->and($usage->getSummary())->toBe('The section is selected as a source for 2 Entries fields.')
        ->and(activeDeletionBlocker('section', $subject, TrashedElementsBlocker::class)->getSummary())
        ->toBe('The section contains 1 entry.');
});

it('offers to move a section’s entries only when another section accepts their types', function () {
    $shared = EntryTypeModel::factory()->create();
    $own = EntryTypeModel::factory()->create();
    $blog = SectionModel::factory()->withEntryTypes($shared, $own)->create();
    $pages = SectionModel::factory()->withEntryTypes($shared)->create();

    EntryModel::factory()->forSection($blog)->forEntryType($own)->createElement();
    EntryModel::factory()->forSection($pages)->forEntryType($shared)->createElement();
    Sections::refreshSections();

    $actionLabels = fn (SectionModel $section) => array_column(
        activeDeletionBlocker('section', Sections::getSectionById($section->id), TrashedElementsBlocker::class)->getActions(),
        'label',
    );

    expect($actionLabels($blog))->not->toContain('Move entries')
        ->and($actionLabels($pages))->toContain('Move entries');
});

it('reports the users in a user group and whether new users join it', function () {
    $groupModel = UserGroupModel::factory()->create();
    $group = UserGroups::getGroupById($groupModel->id);

    expect(activeDeletionBlockers(app(BlockerRegistry::class)->blockers('user-group', $group)))->toBe([]);

    app(ProjectConfig::class)->set('users.defaultGroup', $group->uid);
    expect(activeDeletionBlocker('user-group', $group, UserGroupUsageBlocker::class)->getSummary())
        ->toBe('New users are assigned to the group.');

    $groupModel->users()->attach(UserModel::factory()->create());
    expect(activeDeletionBlocker('user-group', $group, UserGroupUsageBlocker::class)->getSummary())
        ->toBe('1 user is in the group.');
});

it('lets plugins register subject types and add blockers to any type', function () {
    $subjectTypes = app(SubjectTypeRegistry::class);
    $blockerRegistry = app(BlockerRegistry::class);
    $thing = (object) ['name' => 'Plugin thing'];
    $custom = new class implements DeletionBlockerInterface
    {
        public function isActive(): bool
        {
            return true;
        }

        public function getSummary(): string
        {
            return 'Custom';
        }

        public function getDetails(): ?string
        {
            return null;
        }

        public function getActions(): array
        {
            return [];
        }
    };

    $subjectTypes->register('plugin-thing', fn ($id) => $id === 'abc' ? $thing : null);
    $blockerRegistry->register('plugin-thing', fn (object $subject) => $subject === $thing ? [$custom] : []);
    $blockerRegistry->register('user-group', fn () => [$custom]);

    $groupModel = UserGroupModel::factory()->create();
    $userGroupBlockers = $blockerRegistry->blockers('user-group', UserGroups::getGroupById($groupModel->id));

    expect($subjectTypes->find('plugin-thing', 'abc'))->toBe($thing)
        ->and($subjectTypes->find('plugin-thing', 'xyz'))->toBeNull()
        ->and($subjectTypes->label('plugin-thing', $thing))->toBe('Plugin thing')
        ->and($blockerRegistry->blockers('plugin-thing', $thing))->toBe([$custom])
        ->and($blockerRegistry->blockers('plugin-thing', (object) []))->toBe([])
        ->and($userGroupBlockers)->toHaveCount(2)
        ->and($userGroupBlockers[0])->toBeInstanceOf(UserGroupUsageBlocker::class)
        ->and($userGroupBlockers[1])->toBe($custom)
        ->and(fn () => $subjectTypes->find('nonexistent', 1))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $blockerRegistry->register('nonexistent', fn () => []))->toThrow(InvalidArgumentException::class);
});

it('returns a subject’s active blockers to admins', function () {
    $entryType = EntryTypeModel::factory()->create();
    $section = SectionModel::factory()->withEntryTypes($entryType)->create(['name' => 'Blog']);
    EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement();
    Sections::refreshSections();

    postJson(action(DeletionBlockersController::class), ['type' => 'section', 'id' => $section->id])
        ->assertOk()
        ->assertJsonPath('label', 'Blog')
        ->assertJsonPath('blockers.0.summary', 'The section contains 1 entry.')
        ->assertJsonPath('blockers.0.expanded', false)
        ->assertJsonCount(1, 'blockers');

    postJson(action(DeletionBlockersController::class), ['type' => 'nonexistent', 'id' => 1])->assertBadRequest();
    postJson(action(DeletionBlockersController::class), ['type' => 'section', 'id' => 999999])->assertBadRequest();

    $editor = UserModel::factory()->createElement();
    UserPermissions::saveUserPermissions($editor->id, ['accessCp']);
    actingAs($editor);
    postJson(action(DeletionBlockersController::class), ['type' => 'section', 'id' => $section->id])->assertForbidden();
});
