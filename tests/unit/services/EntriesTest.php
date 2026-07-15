<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\Entry;
use craft\elements\User;
use craft\services\Entries;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\UserFixture;
use UnitTester;

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
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->entries = Craft::$app->getEntries();

        $this->userA = User::find()->username('user2')->one();
        $this->userB = User::find()->username('user3')->one();

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
}
