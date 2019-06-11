<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\services;


use Codeception\Test\Unit;
use Craft;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidElementException;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\Revisions;
use crafttests\fixtures\EntryFixture;
use Throwable;
use UnitTester;
use yii\base\Exception;

/**
 * Unit tests for drafts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class DraftsTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Elements
     */
    protected $elements;

    /**
     * @var Drafts
     */
    protected $drafts;

    /**
     * @var Revisions
     */
    protected $revisions;

    // Fixtures
    // =========================================================================

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class
            ],
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * Test applying draft changes to an entry
     *
     * @throws Throwable
     */
    public function testApplyDraft()
    {
        $entry = Entry::find()
            ->title('Pending 1')
            ->one();

        $draft = $this->_setupEntryDraft($entry);

        // Change the title and slug
        $draft->title = 'Not Pending';
        $draft->slug = 'not-pending';

        // Houston.... Ready for take-off
        $this->drafts->applyDraft($draft);

        // Re-get the entry (By the same id)
        $newEntry = Entry::find()->id($entry->id)->one();

        // Have the props changed
        $this->assertEquals($entry->id, $newEntry->id);
        $this->assertSame('Not Pending', $newEntry->title);
        $this->assertSame('not-pending', $newEntry->slug);

        // Does the draft exist?
        $this->assertSame(
            [],
            (new Query())->select(['id'])->from([Table::DRAFTS])->column()
        );
    }

    /**
     * @throws InvalidElementException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function testEntryRevisions()
    {
        $entry = Entry::find()
            ->title('With versioning')
            ->one();

        $entry->title = 'With versioning EDITED';
        $entry->revisionNotes = 'I am a change note.';

        // Make sure we're going to get a new dateUpdated value
        sleep(1);

        if (!$this->elements->saveElement($entry)) {
            throw new InvalidElementException($entry);
        }

        /** @var Entry|RevisionBehavior $revision */
        $revision = Entry::find()
            ->revisionOf($entry)
            ->siteId($entry->siteId)
            ->anyStatus()
            ->orderBy(['num' => SORT_DESC])
            ->one();

        $this->assertNotNull($revision);
        $this->assertSame($entry->dateUpdated->format('Y-m-d H:i:s'), $revision->dateCreated->format('Y-m-d H:i:s'));
        $this->assertSame('With versioning EDITED', $revision->title);
        $this->assertSame('I am a change note.', $revision->revisionNotes);
    }

    /**
     * @throws InvalidElementException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function testEntryRevertToVersion()
    {
        $data = $this->_setupEntryRevert('With versioning', ['title' => 'Changed title']);
        /** @var Entry $entry */
        $entry = $data['entry'];
        /** @var Entry|RevisionBehavior $v1 */
        $v1 = $data['v1'];

        $this->revisions->revertToRevision($v1, 1);

        $newEntry = Entry::find()
            ->id($entry->id)
            ->one();

        // Old title should now be da one.
        $this->assertSame('With versioning', $newEntry->title);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param string $entryTitle
     * @param array $changes
     * @return array
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidElementException
     * @throws Throwable
     */
    protected function _setupEntryRevert(string $entryTitle, array $changes = []): array
    {
        $entry = Entry::find()
            ->title($entryTitle)
            ->one();

        foreach ($changes as $paramName => $value) {
            $entry->$paramName = $value;
        }

        // Make sure we're going to get a new dateUpdated value
        sleep(1);

        if (!$this->elements->saveElement($entry)) {
            throw new InvalidElementException($entry);
        }

        $v1 = Entry::find()
            ->revisionOf($entry)
            ->siteId($entry->siteId)
            ->anyStatus()
            ->orderBy(['num' => SORT_DESC])
            ->offset(1)
            ->one();

        return ['entry' => $entry, 'v1' => $v1];
    }

    /**
     * @param Entry $entry
     * @return Entry|DraftBehavior
     * @throws Throwable
     */
    protected function _setupEntryDraft(Entry $entry): Entry
    {
        /** @var Entry $draft */
        $draft = $this->drafts->createDraft($entry, 1, 'Test Draft');
        $this->assertInstanceOf(Entry::class, $draft);
        $this->assertNotNull($draft->draftId);
        /** @var DraftBehavior $behavior */
        $behavior = $draft->getBehavior('draft');
        $this->assertNotNull($behavior);
        $this->assertEquals($entry->id, $behavior->sourceId);
        $this->assertEquals(1, $behavior->creatorId);
        $this->assertSame('Test Draft', $behavior->draftName);
        $this->assertNull($behavior->draftNotes);
        return $draft;
    }

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();
        $this->elements = Craft::$app->getElements();
        $this->drafts = Craft::$app->getDrafts();
        $this->revisions = Craft::$app->getRevisions();
    }
}
