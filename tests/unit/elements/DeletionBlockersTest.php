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
use craft\elements\deletionblockers\ResolvableDeletionBlockerInterface;
use craft\elements\deletionblockers\SectionUsageBlocker;
use craft\elements\deletionblockers\TrashedEntriesBlocker;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\User;
use craft\events\DefineDeletionBlockersEvent;
use craft\events\RegisterDeletionSubjectTypesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Entries as EntriesField;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Volume;
use craft\services\DeletionBlockers;
use craft\test\TestCase;
use yii\base\InvalidArgumentException;

/**
 * Tests the deletion blockers for fields, entry types, and sections.
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
        self::assertSame('The field is used by 2 field layouts.', $blocker->getSummary());
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

        self::assertSame(
            ['blockerBlog' => 1, 'blockerPages' => 2],
            array_column($this->activeBlocker($blockers, EntryTypeUsageBlocker::class)->getItems(), 'count', 'label'),
        );
        self::assertSame('3 entries will be moved to the trash.', $this->activeBlocker($blockers, TrashedEntriesBlocker::class)->getSummary());
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

        $blockers = $this->blockers('section', $this->blog);

        $usage = $this->activeBlocker($blockers, SectionUsageBlocker::class);
        self::assertSame(['Blocker Related' => 2], array_column($usage->getItems(), 'count', 'label'));
        self::assertSame('1 entry will be moved to the trash.', $this->activeBlocker($blockers, TrashedEntriesBlocker::class)->getSummary());
        self::assertSame([], $this->activeBlockers($this->blockers('section', $this->createSection('blockerEmpty', [$this->page]))));
    }

    public function testEventHandlersCanAddBlockersToAnyRegisteredType(): void
    {
        $custom = $this->createMock(DeletionBlockerInterface::class);
        $custom->method('isActive')->willReturn(true);
        $service = Craft::$app->getDeletionBlockers();
        $volume = new Volume(['id' => 99, 'name' => 'Uploads']);
        $thing = (object)['name' => 'Plugin thing'];

        $registerHandler = function(RegisterDeletionSubjectTypesEvent $event) use ($thing) {
            $event->types['plugin-thing'] = ['find' => fn($id) => $id === 'abc' ? $thing : null];
        };
        $defineHandler = function(DefineDeletionBlockersEvent $event) use ($custom) {
            if ($event->type === 'volume' && $event->subject->id === 99) {
                $event->blockers[] = $custom;
            }
        };
        Event::on(DeletionBlockers::class, DeletionBlockers::EVENT_REGISTER_SUBJECT_TYPES, $registerHandler);
        Event::on(DeletionBlockers::class, DeletionBlockers::EVENT_DEFINE_BLOCKERS, $defineHandler);

        try {
            // Core types without core blockers have none until a handler adds one
            self::assertSame([$custom], $this->activeBlockers($service->getBlockers('volume', $volume)));
            self::assertSame([], $this->activeBlockers($service->getBlockers('volume', new Volume(['id' => 100]))));

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
