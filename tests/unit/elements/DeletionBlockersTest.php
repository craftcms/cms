<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Craft;
use craft\base\Event;
use craft\elements\deletionblockers\DeletionBlockerInterface;
use craft\elements\deletionblockers\EntryAuthorsBlocker;
use craft\elements\deletionblockers\EntryTypeUsageBlocker;
use craft\elements\deletionblockers\FieldUsageBlocker;
use craft\elements\deletionblockers\FilesystemUsageBlocker;
use craft\elements\deletionblockers\ResolvableDeletionBlockerInterface;
use craft\elements\deletionblockers\SourceUsageBlocker;
use craft\elements\deletionblockers\TrashedElementsBlocker;
use craft\elements\deletionblockers\UserGroupUsageBlocker;
use craft\elements\deletionblockers\UserPhotoVolumeBlocker;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\events\DefineDeletionBlockersEvent;
use craft\events\RegisterDeletionSubjectTypesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets as AssetsField;
use craft\fields\Entries as EntriesField;
use craft\fields\PlainText;
use craft\fields\Tags as TagsField;
use craft\fs\Local;
use craft\models\EntryType;
use craft\models\ImageTransform;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\TagGroup;
use craft\models\UserGroup;
use craft\models\Volume;
use craft\services\DeletionBlockers;
use craft\test\TestCase;
use yii\base\InvalidArgumentException;

/**
 * Tests the core deletion blockers and the service that collects them.
 */
class DeletionBlockersTest extends TestCase
{
    private PlainText $summary;
    private EntriesField $related;
    private EntryType $article;
    private EntryType $page;
    private Section $blog;
    private Section $pages;

    protected function _before(): void
    {
        Craft::$app->getUser()->setIdentity(User::find()->admin()->one());
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        $this->summary = new PlainText(['name' => 'Blocker Summary', 'handle' => 'blockerSummary']);
        $fieldsService->saveField($this->summary);

        foreach (['article' => 'Blocker Article', 'page' => 'Blocker Page'] as $property => $name) {
            $entryType = new EntryType(['name' => $name, 'handle' => 'blocker' . ucfirst($property)]);
            $entryType->setFieldLayout($fieldsService->createLayout([
                'type' => Entry::class,
                'tabs' => [['name' => 'Content', 'elements' => [['type' => CustomField::class, 'fieldUid' => $this->summary->uid]]]],
            ]));
            self::assertTrue($entriesService->saveEntryType($entryType), json_encode($entryType->getErrors()));
            $this->$property = $entryType;
        }

        $this->blog = $this->createSection('blockerBlog', [$this->article, $this->page]);
        $this->pages = $this->createSection('blockerPages', [$this->page]);

        $this->related = new EntriesField(['name' => 'Blocker Related', 'handle' => 'blockerRelated', 'sources' => ["section:{$this->blog->uid}"]]);
        $fieldsService->saveField($this->related);
    }

    protected function _after(): void
    {
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();

        foreach ([$this->summary, $this->related] as $field) {
            $fieldsService->deleteField($field);
        }

        foreach ([$this->blog, $this->pages] as $section) {
            $entriesService->deleteSection($section);
        }

        foreach ([$this->article, $this->page] as $entryType) {
            $entriesService->deleteEntryType($entryType);
        }

        Craft::$app->getUser()->setIdentity(null);
    }

    public function testFieldBlockerCountsCanonicalElementsWithValuesPerLayout(): void
    {
        // Two articles with values, one without, and one with a value that's trashed
        $this->createEntry($this->blog, $this->article, 'First');
        $second = $this->createEntry($this->blog, $this->article, 'Second');
        $this->createEntry($this->blog, $this->article, null);
        $trashed = $this->createEntry($this->blog, $this->article, 'Trashed');
        Craft::$app->getElements()->deleteElement($trashed);

        // A draft with a value, of a page that has none
        $page = $this->createEntry($this->pages, $this->page, null);
        $draft = Craft::$app->getDrafts()->createDraft($page);
        $draft->setFieldValue('blockerSummary', 'Draft value');
        Craft::$app->getElements()->saveElement($draft);

        // A draft of an article shouldn't be counted on top of its canonical entry
        Craft::$app->getDrafts()->createDraft($second);

        $blocker = $this->activeBlocker($this->blockers('field', $this->summary), FieldUsageBlocker::class);

        self::assertSame('usages', $blocker->getKey());
        self::assertSame(
            ['Blocker Article' => 2, 'Blocker Page' => 0],
            array_column($blocker->getItems(), 'count', 'label'),
        );
        self::assertSame('2 entries have a value.', $blocker->getItems()[0]['description']);
        self::assertSame('The field is included in 2 field layouts.', $blocker->getSummary());
    }

    public function testUnusedFieldHasNoActiveBlockers(): void
    {
        self::assertSame([], $this->activeBlockers($this->blockers('field', $this->related)));
    }

    public function testEntryTypeBlockersListUsagesAndEntriesToTrash(): void
    {
        $this->createEntry($this->blog, $this->page, null);
        $this->createEntry($this->pages, $this->page, null);
        $this->createEntry($this->pages, $this->page, null);
        $this->createEntry($this->blog, $this->article, null);

        $blockers = $this->blockers('entry-type', $this->page);

        $usage = $this->activeBlocker($blockers, EntryTypeUsageBlocker::class);
        self::assertSame(['blockerBlog' => 1, 'blockerPages' => 2], array_column($usage->getItems(), 'count', 'label'));
        self::assertSame('The entry type is used by 2 sections.', $usage->getSummary());
        self::assertSame('3 entries use the entry type.', $this->activeBlocker($blockers, TrashedElementsBlocker::class)->getSummary());
    }

    public function testSectionBlockersListSourceFieldsAndEntriesToTrash(): void
    {
        $layout = $this->page->getFieldLayout();
        $layout->setTabs([['name' => 'Content', 'elements' => [
            ['type' => CustomField::class, 'fieldUid' => $this->summary->uid],
            ['type' => CustomField::class, 'fieldUid' => $this->related->uid],
        ]]]);
        self::assertTrue(Craft::$app->getEntries()->saveEntryType($this->page), json_encode($this->page->getErrors()));

        $target = $this->createEntry($this->blog, $this->article, null);
        $other = $this->createEntry($this->pages, $this->page, null);

        // Two entries relate to the blog entry, and one relates only to an entry in another section
        foreach ([[$target], [$target], [$other]] as $relatedIds) {
            $source = new Entry(['sectionId' => $this->pages->id, 'typeId' => $this->page->id, 'title' => 'Source']);
            $source->setFieldValue('blockerRelated', array_map(fn(Entry $entry) => $entry->id, $relatedIds));
            $this->saveEntry($source);
        }

        // An existing entry saved with an empty relation stores `[]`, which isn't a value
        $other->setFieldValue('blockerRelated', []);
        $this->saveEntry($other);
        self::assertSame(
            ['Blocker Page' => 3],
            array_column($this->activeBlocker($this->blockers('field', $this->related), FieldUsageBlocker::class)->getItems(), 'count', 'label'),
        );

        // A field offering all sources offers this one too
        $anything = new EntriesField(['name' => 'Blocker Anything', 'handle' => 'blockerAnything', 'sources' => '*']);
        Craft::$app->getFields()->saveField($anything);

        // The section delete trashes unpublished drafts too
        $unpublished = new Entry(['sectionId' => $this->blog->id, 'typeId' => $this->article->id, 'title' => 'Unpublished']);
        self::assertTrue(Craft::$app->getDrafts()->saveElementAsDraft($unpublished, markAsSaved: false));

        try {
            $blockers = $this->blockers('section', $this->blog);
            $usage = $this->activeBlocker($blockers, SourceUsageBlocker::class);
            $counts = array_column($usage->getItems(), 'count', 'label');
            ksort($counts);
            self::assertSame(['Blocker Anything' => 0, 'Blocker Related' => 2], $counts);
            self::assertSame('2 elements relate to blockerBlog entries.', array_column($usage->getItems(), 'description', 'label')['Blocker Related']);
            self::assertSame('The section contains 2 entries.', $this->activeBlocker($blockers, TrashedElementsBlocker::class)->getSummary());
        } finally {
            Craft::$app->getFields()->deleteField($anything);
        }
        self::assertSame([], $this->activeBlockers($this->blockers('section', $this->createSection('blockerEmpty', [$this->page]))));
    }

    public function testTagGroupBlockersListSingleSourceFieldsAndTagsToTrash(): void
    {
        $group = new TagGroup(['name' => 'Blocker Tags', 'handle' => 'blockerTags']);
        self::assertTrue(Craft::$app->getTags()->saveTagGroup($group), json_encode($group->getErrors()));
        // Tags fields select one source, rather than a list of them
        $field = new TagsField(['name' => 'Blocker Tag Field', 'handle' => 'blockerTagField', 'source' => "taggroup:$group->uid"]);
        Craft::$app->getFields()->saveField($field);

        try {
            $tag = new Tag(['groupId' => $group->id, 'title' => 'Blocker tag']);
            self::assertTrue(Craft::$app->getElements()->saveElement($tag));

            $layout = $this->page->getFieldLayout();
            $layout->setTabs([['name' => 'Content', 'elements' => [['type' => CustomField::class, 'fieldUid' => $field->uid]]]]);
            self::assertTrue(Craft::$app->getEntries()->saveEntryType($this->page), json_encode($this->page->getErrors()));
            $entry = new Entry(['sectionId' => $this->pages->id, 'typeId' => $this->page->id, 'title' => 'Tagged']);
            $entry->setFieldValue('blockerTagField', [$tag->id]);
            $this->saveEntry($entry);

            $blockers = $this->blockers('tag-group', $group);
            $usage = $this->activeBlocker($blockers, SourceUsageBlocker::class);
            self::assertSame(['Blocker Tag Field' => 1], array_column($usage->getItems(), 'count', 'label'));
            self::assertSame('The tag group is selected as a source for 1 Tags field.', $usage->getSummary());
            self::assertSame('The tag group contains 1 tag.', $this->activeBlocker($blockers, TrashedElementsBlocker::class)->getSummary());
        } finally {
            Craft::$app->getFields()->deleteField($field);
            Craft::$app->getTags()->deleteTagGroup($group);
        }
    }

    public function testFilesystemAndVolumeBlockersListWhatUsesThem(): void
    {
        $fsService = Craft::$app->getFs();
        $fs = $fsService->createFilesystem(['type' => Local::class, 'name' => 'Blocker Fs', 'handle' => 'blockerFs', 'path' => sys_get_temp_dir() . '/blocker-fs']);
        self::assertTrue($fsService->saveFilesystem($fs), json_encode($fs->getErrors()));
        $volume = new Volume(['name' => 'Blocker Volume', 'handle' => 'blockerVolume', 'fsHandle' => 'blockerFs', 'transformFsHandle' => 'blockerFs']);
        self::assertTrue(Craft::$app->getVolumes()->saveVolume($volume), json_encode($volume->getErrors()));
        $field = new AssetsField(['name' => 'Blocker Images', 'handle' => 'blockerImages', 'sources' => ["volume:$volume->uid"]]);
        Craft::$app->getFields()->saveField($field);
        $projectConfig = Craft::$app->getProjectConfig();
        $photoVolumeUid = $projectConfig->get('users.photoVolumeUid');

        try {
            $blocker = $this->activeBlockers($this->blockers('filesystem', $fs));
            self::assertCount(1, $blocker);
            self::assertInstanceOf(FilesystemUsageBlocker::class, $blocker[0]);
            self::assertSame('The filesystem is used by 1 volume. Choose another filesystem for it first.', $blocker[0]->getSummary());
            // There’s nothing to resolve from the modal
            self::assertSame([], $blocker[0]->getActions());
            self::assertSame([], $this->activeBlockers($this->blockers('filesystem', $fsService->createFilesystem(['type' => Local::class, 'handle' => 'blockerUnused']))));

            $usage = $this->activeBlocker($this->blockers('volume', $volume), SourceUsageBlocker::class);
            self::assertSame(0, array_column($usage->getItems(), 'count', 'label')['Blocker Images'] ?? null);

            // Only the user photo volume gets the user photo blocker
            self::assertSame([], array_filter($this->activeBlockers($this->blockers('volume', $volume)), fn($blocker) => $blocker instanceof UserPhotoVolumeBlocker));
            $projectConfig->set('users.photoVolumeUid', $volume->uid);
            $photos = $this->activeBlocker($this->blockers('volume', $volume), UserPhotoVolumeBlocker::class);
            self::assertSame(['User Photos' => 0], array_column($photos->getItems(), 'count', 'label'));
        } finally {
            $projectConfig->set('users.photoVolumeUid', $photoVolumeUid);
            Craft::$app->getFields()->deleteField($field);
            Craft::$app->getVolumes()->deleteVolume($volume);
            $fsService->removeFilesystem($fs);
        }
    }

    public function testUserGroupBlockerListsUsersAndDefaultGroup(): void
    {
        $group = new UserGroup(['name' => 'Blocker Group', 'handle' => 'blockerGroup']);
        self::assertTrue(Craft::$app->getUserGroups()->saveGroup($group), json_encode($group->getErrors()));
        $projectConfig = Craft::$app->getProjectConfig();
        $defaultGroup = $projectConfig->get('users.defaultGroup');

        try {
            self::assertSame([], $this->activeBlockers($this->blockers('user-group', $group)));

            $user = User::find()->admin()->one();
            Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);
            $blocker = $this->activeBlocker($this->blockers('user-group', $group), UserGroupUsageBlocker::class);
            self::assertSame('1 user is in the group.', $blocker->getSummary());

            Craft::$app->getUsers()->assignUserToGroups($user->id, []);
            $projectConfig->set('users.defaultGroup', $group->uid);
            $blocker = $this->activeBlocker($this->blockers('user-group', $group), UserGroupUsageBlocker::class);
            self::assertSame('New users are assigned to the group.', $blocker->getSummary());
        } finally {
            $projectConfig->set('users.defaultGroup', $defaultGroup);
            Craft::$app->getUserGroups()->deleteGroup($group);
        }
    }

    public function testEventHandlersCanAddBlockersToAnyRegisteredType(): void
    {
        $custom = $this->createMock(DeletionBlockerInterface::class);
        $custom->method('isActive')->willReturn(true);
        $service = Craft::$app->getDeletionBlockers();
        $transform = new ImageTransform(['id' => 99, 'name' => 'Thumb']);
        $thing = (object)['name' => 'Plugin thing'];

        $registerHandler = function(RegisterDeletionSubjectTypesEvent $event) use ($thing) {
            $event->types['plugin-thing'] = ['find' => fn($id) => $id === 'abc' ? $thing : null];
        };
        $defineHandler = function(DefineDeletionBlockersEvent $event) use ($custom) {
            if ($event->type === 'image-transform' && $event->subject->id === 99) {
                $event->blockers[] = $custom;
            }
        };
        Event::on(DeletionBlockers::class, DeletionBlockers::EVENT_REGISTER_SUBJECT_TYPES, $registerHandler);
        Event::on(DeletionBlockers::class, DeletionBlockers::EVENT_DEFINE_BLOCKERS, $defineHandler);

        try {
            // Core types without core blockers have none until a handler adds one
            self::assertSame([$custom], $this->activeBlockers($service->getBlockers('image-transform', $transform)));
            self::assertSame([], $this->activeBlockers($service->getBlockers('image-transform', new ImageTransform(['id' => 100]))));

            // Plugins can register their own subject types
            $service = new DeletionBlockers();
            self::assertSame($thing, $service->findSubject('plugin-thing', 'abc'));
            self::assertNull($service->findSubject('plugin-thing', 'xyz'));
            self::assertSame('Plugin thing', $service->getSubjectLabel('plugin-thing', $thing));
            self::assertSame($this->blog->id, $service->findSubject('section', (string)$this->blog->id)?->id);
            self::assertSame('blockerBlog', $service->getSubjectLabel('section', $this->blog));
        } finally {
            Event::off(DeletionBlockers::class, DeletionBlockers::EVENT_REGISTER_SUBJECT_TYPES, $registerHandler);
            Event::off(DeletionBlockers::class, DeletionBlockers::EVENT_DEFINE_BLOCKERS, $defineHandler);
        }

        $this->expectException(InvalidArgumentException::class);
        $service->findSubject('nonexistent', 1);
    }

    public function testElementBlockersIncludeTheElementTypesOwnAndHandlerBlockers(): void
    {
        $author = User::find()->admin()->one();
        $entry = new Entry(['sectionId' => $this->pages->id, 'typeId' => $this->page->id, 'title' => 'Authored']);
        $entry->setAuthorIds([$author->id]);
        $this->saveEntry($entry);

        $custom = $this->createMock(DeletionBlockerInterface::class);
        $custom->method('isActive')->willReturn(true);
        $seen = [];
        $handler = function(DefineDeletionBlockersEvent $event) use ($custom, &$seen) {
            if ($event->type === 'element') {
                $seen[] = [$event->subject->ids()->all(), $event->hardDelete];
                $event->blockers[] = $custom;
            }
        };
        Event::on(DeletionBlockers::class, DeletionBlockers::EVENT_DEFINE_BLOCKERS, $handler);

        try {
            $active = $this->activeBlockers(Craft::$app->getDeletionBlockers()->getElementBlockers(User::class, ElementCollection::make([$author]), true));
        } finally {
            Event::off(DeletionBlockers::class, DeletionBlockers::EVENT_DEFINE_BLOCKERS, $handler);
        }

        // The user's own entry-authors blocker comes first, then the handler's
        self::assertInstanceOf(EntryAuthorsBlocker::class, $active[0]);
        self::assertSame($custom, end($active));
        self::assertSame([[[$author->id], true]], $seen);
    }

    /**
     * @return DeletionBlockerInterface[]
     */
    private function blockers(string $type, mixed $subject): array
    {
        return Craft::$app->getDeletionBlockers()->getBlockers($type, $subject);
    }

    /**
     * @param DeletionBlockerInterface[] $blockers
     * @return DeletionBlockerInterface[]
     */
    private function activeBlockers(array $blockers): array
    {
        return array_values(array_filter($blockers, fn(DeletionBlockerInterface $blocker) => $blocker->isActive()));
    }

    /**
     * @template T of ResolvableDeletionBlockerInterface
     * @param DeletionBlockerInterface[] $blockers
     * @param class-string<T> $class
     * @return T
     */
    private function activeBlocker(array $blockers, string $class): ResolvableDeletionBlockerInterface
    {
        $matches = array_values(array_filter($this->activeBlockers($blockers), fn($blocker) => $blocker instanceof $class));
        self::assertCount(1, $matches, "Expected one active $class.");

        return $matches[0];
    }

    /**
     * @param EntryType[] $entryTypes
     */
    private function createSection(string $handle, array $entryTypes): Section
    {
        $section = new Section(['name' => $handle, 'handle' => $handle, 'type' => Section::TYPE_CHANNEL]);
        $section->setEntryTypes($entryTypes);
        $section->setSiteSettings([
            new Section_SiteSettings(['siteId' => Craft::$app->getSites()->getPrimarySite()->id, 'hasUrls' => false]),
        ]);
        self::assertTrue(Craft::$app->getEntries()->saveSection($section), json_encode($section->getErrors()));

        return $section;
    }

    private function createEntry(Section $section, EntryType $entryType, ?string $summary): Entry
    {
        $entry = new Entry(['sectionId' => $section->id, 'typeId' => $entryType->id, 'title' => $summary ?? 'Empty']);
        $entry->setFieldValue('blockerSummary', $summary);
        $this->saveEntry($entry);

        return $entry;
    }

    private function saveEntry(Entry $entry): void
    {
        self::assertTrue(Craft::$app->getElements()->saveElement($entry), implode(', ', $entry->getFirstErrors()));
    }
}
