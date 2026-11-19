<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Table;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\events\EagerLoadElementsEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fields\data\LinkData;
use craft\fields\Entries;
use craft\fields\linktypes\BaseElementLinkType;
use craft\fields\linktypes\Entry as EntryLinkType;
use craft\fields\linktypes\Url;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\queue\Queue as CraftQueue;
use craft\services\Elements;
use craft\test\TestCase;
use crafttests\fixtures\EntryWithFieldsFixture;
use ReflectionProperty;
use WeakReference;
use yii\base\Event;
use yii\queue\ExecEvent;
use yii\queue\Queue;

/**
 * Unit tests for the Link custom field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class LinkTest extends TestCase
{
    /**
     * @inheritdoc
     */
    public function _fixtures(): array
    {
        return [
            'entry-with-fields' => [
                'class' => EntryWithFieldsFixture::class,
            ],
        ];
    }

    public function testLinkedElementsOfQueriedElementsLoadTogether(): void
    {
        $live = $this->createEntry();
        $disabled = $this->createEntry(['enabled' => false]);
        $deleted = $this->createEntry();
        $ids = $this->createLinkOwners([
            $this->entryLink($live),
            $this->entryLink($disabled),
            ['type' => 'url', 'value' => 'https://example.com'],
            $this->entryLink($deleted),
        ]);
        Craft::$app->getElements()->deleteElement($deleted, true);
        $owners = Entry::find()->id($ids)->fixedOrder()->all();

        self::assertSame($live->id, $this->link($owners[0])->getElement()->id);

        Db::delete(Table::ELEMENTS, ['id' => [$live->id, $disabled->id]]);

        self::assertSame($disabled->id, $this->link($owners[1])->getElement()->id);
        self::assertSame('', $this->link($owners[1])->getUrl(false));
        self::assertSame('https://example.com', (string)$this->link($owners[2]));
        self::assertNull($this->link($owners[3])->getElement());
        self::assertSame('', $this->link($owners[3])->getUrl());
    }

    public function testLinkedElementsAndTheirNestedElementsCanBeEagerLoaded(): void
    {
        $targets = [$this->createEntry(), $this->createEntry()];
        $ids = $this->createLinkOwners(array_map(fn(Entry $target) => $this->entryLink($target), $targets));
        $owners = Entry::find()->id($ids)->fixedOrder()->with(['linkField.matrixFirst'])->all();

        self::assertInstanceOf(LinkData::class, $owners[0]->getFieldValue('linkField'));
        self::assertSame(
            array_map(fn(Entry $target) => $target->id, $targets),
            array_map(fn(Entry $owner) => $this->link($owner)->getElement()->id, $owners),
        );
        self::assertTrue($this->link($owners[1])->getElement()->hasEagerLoadedElements('matrixFirst'));
    }

    public function testLinksToSpecificAndCurrentSitesAreEagerLoadedTogether(): void
    {
        $targets = [$this->createEntry(), $this->createEntry()];
        $ids = $this->createLinkOwners([
            $this->entryLink($targets[0]),
            ['type' => 'entry', 'value' => "{entry:{$targets[1]->id}:url}"],
        ]);
        $owners = Entry::find()->id($ids)->fixedOrder()->with(['linkField'])->all();

        Db::delete(Table::ELEMENTS, ['id' => array_map(fn(Entry $target) => $target->id, $targets)]);

        self::assertSame(
            array_map(fn(Entry $target) => $target->id, $targets),
            array_map(fn(Entry $owner) => $this->link($owner)->getElement()?->id, $owners),
        );
    }

    public function testEagerLoadingPlansThatCouldLoadOtherElementsLeaveLinksAlone(): void
    {
        $target = $this->createEntry();
        $ids = $this->createLinkOwners([$this->entryLink($target), $this->entryLink($target)]);
        $filtered = Entry::find()->id($ids)->with([['linkField', ['slug' => 'not-a-target']]])->all();
        $aliased = Entry::find()->id($ids)->with(['linkField as linkedEntry'])->all();

        self::assertSame($target->id, $this->link($filtered[0])->getElement()->id);
        self::assertNull($aliased[0]->getEagerLoadedElements('linkedEntry'));
        self::assertInstanceOf(LinkData::class, $aliased[0]->getFieldValue('linkField'));
    }

    public function testOnlyLinksToASpecificSiteAreEagerLoadedForPlansLimitedToSomeSites(): void
    {
        $targets = [$this->createEntry(), $this->createEntry()];
        $ids = $this->createLinkOwners([
            $this->entryLink($targets[0]),
            ['type' => 'entry', 'value' => "{entry:{$targets[1]->id}:url}"],
        ]);
        $owners = Entry::find()->id($ids)->fixedOrder()->with([
            new EagerLoadPlan(['handle' => 'linkField', 'alias' => 'linkField', 'siteIds' => [$targets[0]->siteId]]),
        ])->all();

        Db::delete(Table::ELEMENTS, ['id' => array_map(fn(Entry $target) => $target->id, $targets)]);

        self::assertSame($targets[0]->id, $this->link($owners[0])->getElement()?->id);
        self::assertNull($this->link($owners[1])->getElement());
    }

    public function testReadingAMissingLinkKeepsTheOtherLinksEagerLoadedElements(): void
    {
        $targets = [$this->createEntry(), $this->createEntry()];
        $deleted = $this->createEntry();
        $ids = $this->createLinkOwners([...array_map(fn(Entry $target) => $this->entryLink($target), $targets), $this->entryLink($deleted)]);
        Craft::$app->getElements()->deleteElement($deleted, true);
        $owners = Entry::find()->id($ids)->fixedOrder()->with(['linkField.matrixFirst'])->all();

        self::assertNull($this->link($owners[2])->getElement());
        self::assertTrue($this->link($owners[0])->getElement()->hasEagerLoadedElements('matrixFirst'));
    }

    public function testLinksThatHaventBeenReadCanBeSerialized(): void
    {
        $target = $this->createEntry();
        $ids = $this->createLinkOwners([$this->entryLink($target), $this->entryLink($target)]);
        $link = $this->link(Entry::find()->id($ids)->fixedOrder()->all()[0]);
        $serialized = serialize($link);

        self::assertStringNotContainsString('Closure', $serialized);
        self::assertSame($target->id, unserialize($serialized)->getElement()->id);
    }

    public function testLinksThatHaveBeenReadAreSerializedWithoutTheirElement(): void
    {
        $target = $this->createEntry();
        $ids = $this->createLinkOwners([$this->entryLink($target), $this->entryLink($target)]);
        $link = $this->link(Entry::find()->id($ids)->fixedOrder()->with(['linkField'])->all()[0]);
        $link->getElement();
        $serialized = serialize($link);

        self::assertStringNotContainsString(sprintf('O:%d:"%s"', strlen(Entry::class), Entry::class), $serialized);
        self::assertSame($target->id, unserialize($serialized)->getElement()->id);
    }

    public function testSerializedLinksCanBeRestored(): void
    {
        $link = unserialize(serialize(new LinkData('https://example.com', new Url(), ['target' => '_blank'])));

        self::assertSame('https://example.com', $link->getValue());
        self::assertSame('url', $link->getType());
        self::assertSame('_blank', $link->target);
    }

    public function testPrivatePropertiesOfLinkDataSubclassesAreKeptWhenSerialized(): void
    {
        $link = new NotedLinkData('https://example.com', new Url());
        $link->setNote('Kept');

        self::assertSame('Kept', unserialize(serialize($link))->note());
    }

    public function testFieldsThatShareAHandleAreAllEagerLoaded(): void
    {
        $entriesService = Craft::$app->getEntries();
        $layout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($layout);
        $tab->setElements([new CustomField(Craft::$app->getFields()->getFieldByHandle('relatedEntry'), ['handle' => 'linkField'])]);
        $layout->setTabs([$tab]);
        $entryType = new EntryType(['name' => 'Related', 'handle' => 'related']);
        $entryType->setFieldLayout($layout);
        $section = new Section(['name' => 'Related', 'handle' => 'related', 'type' => Section::TYPE_CHANNEL]);
        $section->setEntryTypes([$entryType]);
        $section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
                'hasUrls' => false,
            ]),
        ]);
        self::assertTrue($entriesService->saveEntryType($entryType));
        self::assertTrue($entriesService->saveSection($section));

        try {
            $target = $this->createEntry();
            [$linkOwnerId] = $this->createLinkOwners([['type' => 'url', 'value' => 'https://example.com']]);
            $entriesOwner = new Entry([
                'sectionId' => $section->id,
                'typeId' => $entryType->id,
                'title' => StringHelper::randomString(),
            ]);
            $entriesOwner->setFieldValue('linkField', [$target->id]);
            Craft::$app->getElements()->saveElement($entriesOwner, false);
            $owners = Entry::find()->id([$linkOwnerId, $entriesOwner->id])->fixedOrder()->with(['linkField'])->all();

            self::assertSame(
                [$target->id],
                array_map(fn(ElementInterface $element) => $element->id, $owners[1]->getEagerLoadedElements('linkField')?->all() ?? []),
            );
        } finally {
            $entriesService->deleteSection($section);
            $entriesService->deleteEntryType($entryType);
        }
    }

    public function testFieldsThatShareAHandleFetchTheirOwnValuesWhenOneSkipsEagerLoading(): void
    {
        $fieldsService = Craft::$app->getFields();
        $entriesService = Craft::$app->getEntries();
        $skippingField = new SkippingEntries(['name' => 'Skipping', 'handle' => 'skippingRelated']);
        self::assertTrue($fieldsService->saveField($skippingField));
        $skippingType = $this->entryTypeWithField('skipping', $skippingField, 'skippingRelated');
        $entriesType = $this->entryTypeWithField('entries', $fieldsService->getFieldByHandle('relatedEntry'), 'skippingRelated');
        $section = new Section(['name' => 'Skipping', 'handle' => 'skipping', 'type' => Section::TYPE_CHANNEL]);
        $section->setEntryTypes([$skippingType, $entriesType]);
        $section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
                'hasUrls' => false,
            ]),
        ]);
        self::assertTrue($entriesService->saveSection($section));

        try {
            $targets = [$this->createEntry(), $this->createEntry()];
            $ownerIds = [];
            foreach ([$skippingType, $entriesType] as $i => $entryType) {
                $owner = new Entry([
                    'sectionId' => $section->id,
                    'typeId' => $entryType->id,
                    'title' => StringHelper::randomString(),
                ]);
                $owner->setFieldValue('skippingRelated', [$targets[$i]->id]);
                Craft::$app->getElements()->saveElement($owner, false);
                $ownerIds[] = $owner->id;
            }
            $owners = Entry::find()->id($ownerIds)->fixedOrder()->with(['skippingRelated'])->all();

            self::assertSame(
                [$targets[0]->id],
                array_map(fn(ElementInterface $element) => $element->id, $owners[0]->getFieldValue('skippingRelated')->all()),
            );
        } finally {
            $entriesService->deleteSection($section);
            $entriesService->deleteEntryType($skippingType);
            $entriesService->deleteEntryType($entriesType);
            $fieldsService->deleteField($skippingField);
        }
    }

    public function testLinksSkippedByTheirBatchStillResolve(): void
    {
        $target = $this->createEntry();
        $ids = $this->createLinkOwners([$this->entryLink($target), $this->entryLink($target)]);
        $skipPlans = function(EagerLoadElementsEvent $event) {
            $event->with = [];
        };
        Craft::$app->getElements()->on(Elements::EVENT_BEFORE_EAGER_LOAD_ELEMENTS, $skipPlans);

        try {
            $owners = Entry::find()->id($ids)->fixedOrder()->all();

            self::assertSame($target->id, $this->link($owners[0])->getElement()?->id);
        } finally {
            Craft::$app->getElements()->off(Elements::EVENT_BEFORE_EAGER_LOAD_ELEMENTS, $skipPlans);
        }
    }

    public function testFetchedElementsAreKeptApartPerLinkType(): void
    {
        $target = $this->createEntry();
        $ids = $this->createLinkOwners([$this->entryLink($target), $this->entryLink($target)]);
        Entry::find()->id($ids)->with(['linkField'])->all();

        self::assertNull((new SlugScopedEntryLinkType())->element($this->entryLink($target)['value']));
    }

    public function testQueueJobsDontKeepTheLinkedElementsOfEarlierJobs(): void
    {
        $targets = [$this->createEntry(), $this->createEntry()];
        $ids = $this->createLinkOwners(array_map(fn(Entry $target) => $this->entryLink($target), $targets));
        $owners = Entry::find()->id($ids)->with(['linkField'])->all();
        foreach ($owners as $owner) {
            $this->link($owner)->getUrl();
        }
        $source = WeakReference::create($owners[0]);
        unset($owners, $owner);

        Event::trigger(CraftQueue::class, Queue::EVENT_BEFORE_EXEC, new ExecEvent());
        gc_collect_cycles();

        self::assertNull($source->get());
    }

    private function entryTypeWithField(string $handle, FieldInterface $field, string $fieldHandle): EntryType
    {
        $layout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($layout);
        $tab->setElements([new CustomField($field, ['handle' => $fieldHandle])]);
        $layout->setTabs([$tab]);
        $entryType = new EntryType(['name' => $handle, 'handle' => $handle]);
        $entryType->setFieldLayout($layout);
        self::assertTrue(Craft::$app->getEntries()->saveEntryType($entryType));

        return $entryType;
    }

    private function createEntry(array $attributes = []): Entry
    {
        $entry = new Entry([
            'sectionId' => 1000,
            'typeId' => 1000,
            'title' => StringHelper::randomString(),
            ...$attributes,
        ]);
        Craft::$app->getElements()->saveElement($entry, false);

        return $entry;
    }

    /**
     * @param array[] $values
     * @return int[]
     */
    private function createLinkOwners(array $values): array
    {
        $ids = array_map(function(array $value) {
            $owner = new Entry([
                'sectionId' => 1000,
                'typeId' => 1000,
                'title' => StringHelper::randomString(),
            ]);
            $owner->setFieldValue('linkField', $value);
            Craft::$app->getElements()->saveElement($owner, false);

            return $owner->id;
        }, $values);

        (new ReflectionProperty(BaseElementLinkType::class, 'fetchedElements'))->setValue(null, []);

        return $ids;
    }

    private function link(Entry $owner): LinkData
    {
        $link = $owner->getFieldValue('linkField');
        self::assertInstanceOf(LinkData::class, $link);

        return $link;
    }

    private function entryLink(Entry $target): array
    {
        return ['type' => 'entry', 'value' => "{entry:$target->id@$target->siteId:url}"];
    }
}

class NotedLinkData extends LinkData
{
    private string $note = '';

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function note(): string
    {
        return $this->note;
    }
}

class SlugScopedEntryLinkType extends EntryLinkType
{
    public function elementQuery(?string $value): ?ElementQueryInterface
    {
        return parent::elementQuery($value)?->slug('not-a-target');
    }
}

class SkippingEntries extends Entries
{
    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        return null;
    }
}
