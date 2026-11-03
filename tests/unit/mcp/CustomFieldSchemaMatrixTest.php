<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\mcp\schema\CustomFieldSchema;
use craft\test\TestCase;
use crafttests\fixtures\NemFieldLayoutFixture;
use crafttests\fixtures\NemSectionFixture;
use crafttests\fixtures\SitesFixture;
use Mcp\Exception\ToolCallException;
use UnitTester;

class CustomFieldSchemaMatrixTest extends TestCase
{
    protected UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'sites' => ['class' => SitesFixture::class],
            'nemFieldLayout' => ['class' => NemFieldLayoutFixture::class],
            'nemSection' => ['class' => NemSectionFixture::class],
        ];
    }

    public function testNewEntriesAreSavedWithoutSortOrder(): void
    {
        $owner = $this->save($this->owner(), [
            'entries' => [
                ['nemBlock' => ['id' => 'new1', 'nemText' => 'First']],
                ['nemBlock' => ['nemText' => 'Second']],
            ],
        ]);

        self::assertSame(['First', 'Second'], $this->texts($owner));
    }

    public function testNewEntriesWithoutSortOrderAreAddedAfterExistingEntries(): void
    {
        $owner = $this->save($this->owner(), [
            'sortOrder' => ['new1'],
            'entries' => [['nemBlock' => ['id' => 'new1', 'nemText' => 'Existing']]],
        ]);

        $owner = $this->save(Entry::find()->id($owner->id)->one(), [
            'entries' => [['nemBlock' => ['id' => 'new1', 'nemText' => 'Added']]],
        ]);

        self::assertSame(['Existing', 'Added'], $this->texts($owner));
    }

    public function testSortOrderMustListNewEntries(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('nemMatrix: Add new2 to `sortOrder`');

        (new CustomFieldSchema())->prepareValues($this->owner(), ['nemMatrix' => [
            'sortOrder' => ['new1'],
            'entries' => [
                ['nemBlock' => ['id' => 'new1', 'nemText' => 'Listed']],
                ['nemBlock' => ['id' => 'new2', 'nemText' => 'Unlisted']],
            ],
        ]]);
    }

    private function owner(): Entry
    {
        return new Entry([
            'sectionId' => 3000,
            'typeId' => 3000,
            'title' => 'MCP Matrix owner',
        ]);
    }

    private function save(Entry $owner, array $matrixValue): Entry
    {
        $owner->setFieldValues((new CustomFieldSchema())->prepareValues($owner, ['nemMatrix' => $matrixValue]));

        if (!Craft::$app->getElements()->saveElement($owner)) {
            throw new InvalidElementException($owner);
        }

        return $owner;
    }

    /** @return list<string|null> */
    private function texts(Entry $owner): array
    {
        return array_map(
            static fn(Entry $entry): ?string => $entry->getFieldValue('nemText'),
            Entry::find()->ownerId($owner->id)->status(null)->orderBy(['elements_owners.sortOrder' => SORT_ASC])->all(),
        );
    }
}
