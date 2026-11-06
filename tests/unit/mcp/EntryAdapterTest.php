<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\Entry;
use craft\elements\User;
use craft\mcp\elements\adapters\EntryAdapter;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use Mcp\Exception\ToolCallException;

class EntryAdapterTest extends TestCase
{
    public function _fixtures(): array
    {
        return ['entries' => ['class' => EntryFixture::class]];
    }

    public function testCreateAndUpdateAcceptDateStrings(): void
    {
        $adapter = $this->adapter();
        $admin = User::find()->admin()->one();

        $result = $adapter->create([
            'sectionId' => 1011,
            'title' => 'MCP dates',
            'postDate' => '2026-01-15T10:00:00+02:00',
            'expiryDate' => '2027-01-15T00:00:00Z',
        ], [], $admin);

        $entry = Entry::find()->id($result['element']['id'])->status(null)->one();
        self::assertSame(strtotime('2026-01-15T08:00:00Z'), $entry->postDate->getTimestamp());
        self::assertSame(strtotime('2027-01-15T00:00:00Z'), $entry->expiryDate->getTimestamp());

        $adapter->update($entry, ['expiryDate' => null], [], $admin);

        self::assertNull(Entry::find()->id($entry->id)->status(null)->one()->expiryDate);
    }

    public function testRejectsInvalidDates(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid postDate value.');

        $this->adapter()->create([
            'sectionId' => 1011,
            'postDate' => 'not-a-date',
        ], [], User::find()->admin()->one());
    }

    private function adapter(): EntryAdapter
    {
        return Craft::$app->get('mcp')->getCapabilityContainer()->get(EntryAdapter::class);
    }
}
