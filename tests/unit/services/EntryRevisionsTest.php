<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\services;


use Codeception\Test\Unit;
use craft\db\Query;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidElementException;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\records\EntryDraft;
use craft\records\EntryVersion;
use craft\services\EntryRevisions;
use craftunit\fixtures\EntryFixture;
use craftunit\fixtures\EntryDraftsFixture;
use \craft\models\EntryDraft as EntryDraftModel;
use UnitTester;
use Craft;
use Throwable;
use yii\base\Exception;

/**
 * Unit tests for the garbage collector service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class EntryRevisionsTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester $tester
     */
    protected $tester;

    /**
     * @var EntryRevisions $entryRevisions
     */
    protected $entryRevisions;

    // Fixtures
    // =========================================================================

    /**
     * @return array
     */
    public function _fixtures() : array
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
     * Test various features of publishing an entry draft.
     * @throws \yii\db\Exception
     */
    public function testPublishDraftPublishesDraft()
    {
        $entry = Entry::find()
            ->title('Pending 1')
            ->one();

        $entryDraft = $this->_setupEntryDraft($entry);

        $entryDraft->revisionNotes = null;

        // Houston.... Ready for take-off
        $this->entryRevisions->publishDraft($entryDraft);

        // Re-get the entry (By the same id)
        $newEntry = Entry::find()->id($entry->id)->one();

        // Have the props changed
        $this->assertSame($entry->id, $newEntry->id);
        $this->assertSame('not--pending', $newEntry->slug);
        $this->assertSame('Not pending', $newEntry->title);
        $this->assertNotNull($entryDraft->revisionNotes);

        // Does the draft exist?
        $this->assertSame(
            [],
            (new Query())->select('id')->from(EntryDraft::tableName())->column()
        );
    }

    /**
     * @throws InvalidElementException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function testEntryVersioning()
    {
        $entry = Entry::find()
            ->title('With versioning')
            ->one();

        $entry->title = 'With versioning EDITED';
        $entry->revisionNotes = 'Iam a change note.';
        if (!Craft::$app->getElements()->saveElement($entry)) {
            throw new InvalidElementException($entry);
        }

        $versions = (new Query())
            ->select('*')
            ->from(EntryVersion::tableName())
            ->where(['entryId' => $entry->id])
            ->all();

        $this->assertCount(2, $versions);

        $version1 = $versions[0];
        $version1Data = Json::decode($version1['data']);

        // Check version 1
        $this->assertNull($version1['notes']);
        $this->assertSame('1', $version1['num']);
        $this->assertSame('With versioning', $version1Data['title']);
        $this->assertSame('With--versioning', $version1Data['slug']);

        // Check version 2
        $version2 = $versions[1];
        $version2Data = Json::decode($version2['data']);
        $this->assertSame('2', $version2['num']);
        $this->assertSame('Iam a change note.', $version2['notes']);
        $this->assertSame('With versioning EDITED', $version2Data['title']);
        $this->assertSame('With--versioning', $version2Data['slug']);
    }

    /**
     *
     */
    public function testPublishDraftOnSingle()
    {
        $entry = Entry::find()->title('Single entry')->one();
        $entryDraft = $this->_setupEntryDraft($entry);

        $this->entryRevisions->publishDraft($entryDraft);

        // Title of section is single.
        $this->assertSame('Single', $entryDraft->title);
    }

    /**
     *
     */
    public function testPublishDraftCustomRevisionNotes()
    {
        $entry = Entry::find()->title('Single entry')->one();
        $entryDraft = $this->_setupEntryDraft($entry);
        $entryDraft->revisionNotes = 'Custom revision notes';

        $this->entryRevisions->publishDraft($entryDraft);

        // Ensure that our own notes dont get overriden
        $this->assertSame( 'Custom revision notes', $entryDraft->revisionNotes);
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
        $entry = $data['entry'];
        $v1 = $data['v1'];

        $this->entryRevisions->revertEntryToVersion($v1);

        $newEntry = Entry::find()
            ->id($entry->id)
            ->one();

        // Old title should now be da one.
        $this->assertSame('With versioning', $newEntry->title);
        $this->assertSame('Reverted version 1.', $v1->revisionNotes);
    }

    /**
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidElementException
     * @throws Throwable
     */
    public function testEntryVersionResetOnSingle()
    {
        $data = $this->_setupEntryRevert('Single entry');
        $entry = $data['entry'];
        $v1 = $data['v1'];

        $this->entryRevisions->revertEntryToVersion($v1);

        $newEntry = Entry::find()
            ->id($entry->id)
            ->one();

        // The name of the section should be used when reverting a an entry that is in a 'single' section.
        $this->assertSame('Single', $newEntry->title);
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
    protected function _setupEntryRevert(string $entryTitle, array $changes = []) : array
    {
        $entry = Entry::find()
            ->title($entryTitle)
            ->one();

        foreach ($changes as $paramName => $value) {
            $entry->$paramName = $value;
        }

        if (!Craft::$app->getElements()->saveElement($entry)) {
            throw new InvalidElementException($entry);
        }

        $versions = $this->entryRevisions->getVersionsByEntryId($entry->id, null, null,true);
        $v1 = ArrayHelper::firstValue(
            ArrayHelper::filterByValue(
                $versions,
                'num',
                '1'
            )
        );

        return ['entry' => $entry, 'v1' => $v1];
    }

    /**
     * @param Entry $entry
     * @return EntryDraftModel
     * @throws \yii\db\Exception
     */
    protected function _setupEntryDraft(Entry $entry) : EntryDraftModel
    {
        Craft::$app->getDb()->createCommand()->insert(EntryDraft::tableName(), [
            'entryId' => $entry->id,
            'sectionId' => $entry->sectionId,
            'siteId' => $entry->siteId,
            'creatorId' => 1,
            'name' => 'Data',
            'data' => '{"typeId":"'.$entry->typeId.'","authorId":"1","title":"Not pending","slug":"not-pending","expiryDate":null,"enabled":true}'
        ])->execute();

        return ArrayHelper::firstValue(
            Craft::$app->getEntryRevisions()->getDraftsByEntryId($entry->id)
        );
    }
    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();
        $this->entryRevisions = Craft::$app->getEntryRevisions();
    }
}
