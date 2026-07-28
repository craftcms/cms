<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\db\Command;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\elements\User;
use craft\services\Entries;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\UserFixture;
use UnitTester;
use yii\db\Exception as DbException;

/**
 * Unit tests for the Entries service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class EntriesTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var Entries
     */
    protected Entries $entries;

    /**
     * @var Entry
     */
    protected Entry $entryA;

    /**
     * @var Entry
     */
    protected Entry $entryB;

    /**
     * @var User
     */
    protected User $userA;

    /**
     * @var User
     */
    protected User $userB;

    /**
     * @var User
     */
    protected User $userC;

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class,
            ],
        ];
    }

    /**
     * @throws \Throwable
     */
    public function testReassignEntries(): void
    {
        $count = $this->entries->reassignEntries($this->userA->id, $this->userB->id);
        self::assertSame(1, $count);

        /** @var Entry $reassignedEntry */
        $reassignedEntry = Entry::find()->id($this->entryA->id)->one();
        self::assertSame($this->userB->id, $reassignedEntry->getAuthorId());

        // The entry that already belonged to the new author should be untouched.
        /** @var Entry $untouchedEntry */
        $untouchedEntry = Entry::find()->id($this->entryB->id)->one();
        self::assertSame($this->userB->id, $untouchedEntry->getAuthorId());
    }

    /**
     * @throws \Throwable
     */
    public function testSaveNewEntryAuthors(): void
    {
        $entry = $this->createEntry([$this->userA->id, $this->userB->id]);
        $db = Craft::$app->getDb();
        $commandClass = $db->commandClass;
        $db->commandClass = EntriesTestCommand::class;
        EntriesTestCommand::$executedSql = [];

        try {
            $this->tester->saveElement($entry);

            self::assertSame([
                [$this->userA->id, 1],
                [$this->userB->id, 2],
            ], $this->authorRows($entry));
            self::assertSame([], EntriesTestCommand::authorDeleteQueries());

            /** @var Entry $savedEntry */
            $savedEntry = Entry::find()->id($entry->id)->status(null)->one();
            self::assertSame([$this->userA->id, $this->userB->id], $savedEntry->getAuthorIds());
        } finally {
            $db->commandClass = $commandClass;
            if ($entry->id) {
                $this->tester->deleteElement($entry);
            }
        }
    }

    /**
     * @throws \Throwable
     */
    public function testUpdateEntryAuthors(): void
    {
        $entry = $this->createEntry([$this->userA->id, $this->userB->id]);
        $this->tester->saveElement($entry);

        try {
            $entry->setAuthorIds([$this->userB->id, $this->userA->id]);
            $this->tester->saveElement($entry);
            self::assertSame([
                [$this->userB->id, 1],
                [$this->userA->id, 2],
            ], $this->authorRows($entry));

            $entry->setAuthorIds([$this->userB->id, $this->userC->id]);
            $this->tester->saveElement($entry);
            self::assertSame([
                [$this->userB->id, 1],
                [$this->userC->id, 2],
            ], $this->authorRows($entry));

            $entry->setAuthorIds([]);
            $this->tester->saveElement($entry);
            self::assertSame([], $this->authorRows($entry));
        } finally {
            $this->tester->deleteElement($entry);
        }
    }

    /**
     * @throws \Throwable
     */
    public function testAuthorInsertFailureRollsBackChanges(): void
    {
        $entry = $this->createEntry([$this->userA->id, $this->userB->id]);
        $this->tester->saveElement($entry);

        $db = Craft::$app->getDb();
        $commandClass = $db->commandClass;
        $db->commandClass = EntriesTestCommand::class;
        EntriesTestCommand::$failAuthorInsert = true;
        $entry->setAuthorIds([$this->userB->id, $this->userC->id]);

        try {
            $this->tester->saveElement($entry);
            self::fail('The author insert should have failed.');
        } catch (DbException $e) {
            self::assertSame('Forced entries_authors insert failure.', $e->getMessage());
            self::assertSame([
                [$this->userA->id, 1],
                [$this->userB->id, 2],
            ], $this->authorRows($entry));
        } finally {
            EntriesTestCommand::$failAuthorInsert = false;
            $db->commandClass = $commandClass;
            $this->tester->deleteElement($entry);
        }
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->entries = Craft::$app->getEntries();

        $this->userA = User::find()->username('user2')->one();
        $this->userB = User::find()->username('user3')->one();
        $this->userC = User::find()->username('user4')->one();

        $this->entryA = new Entry([
            'sectionId' => 1000,
            'typeId' => 1000,
            'title' => 'Reassign Entries Test A',
            'authorId' => $this->userA->id,
        ]);
        $this->tester->saveElement($this->entryA);

        $this->entryB = new Entry([
            'sectionId' => 1000,
            'typeId' => 1000,
            'title' => 'Reassign Entries Test B',
            'authorId' => $this->userB->id,
        ]);
        $this->tester->saveElement($this->entryB);
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        $this->tester->deleteElement($this->entryA);
        $this->tester->deleteElement($this->entryB);

        parent::_after();
    }

    private function createEntry(array $authorIds): Entry
    {
        return new Entry([
            'sectionId' => 1011,
            'typeId' => 1011,
            'title' => 'Save Authors Test',
            'authorIds' => $authorIds,
        ]);
    }

    private function authorRows(Entry $entry): array
    {
        $rows = (new Query())
            ->select(['authorId', 'sortOrder'])
            ->from(Table::ENTRIES_AUTHORS)
            ->where(['entryId' => $entry->id])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        return array_map(fn(array $row) => [
            (int)$row['authorId'],
            (int)$row['sortOrder'],
        ], $rows);
    }
}

class EntriesTestCommand extends Command
{
    public static array $executedSql = [];

    public static bool $failAuthorInsert = false;

    public function execute()
    {
        $sql = $this->getRawSql();
        self::$executedSql[] = $sql;

        if (
            self::$failAuthorInsert &&
            preg_match('/^INSERT\s+INTO\b.*entries_authors/is', $sql)
        ) {
            throw new DbException('Forced entries_authors insert failure.');
        }

        return parent::execute();
    }

    public static function authorDeleteQueries(): array
    {
        return array_values(array_filter(
            self::$executedSql,
            fn(string $sql) => preg_match('/^DELETE\s+FROM\b.*entries_authors/is', $sql),
        ));
    }
}
