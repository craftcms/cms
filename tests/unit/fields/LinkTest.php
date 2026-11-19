<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use Craft;
use craft\db\Table;
use craft\elements\db\EagerLoadPlan;
use craft\elements\Entry;
use craft\fields\data\LinkData;
use craft\fields\linktypes\BaseElementLinkType;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\test\TestCase;
use crafttests\fixtures\EntryWithFieldsFixture;
use ReflectionProperty;

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

        self::assertSame($target->id, unserialize(serialize($link))->getElement()->id);
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
