<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Craft;
use craft\db\Table;
use craft\elements\Address;
use craft\elements\Entry;
use craft\elements\NestedElementManager;
use craft\elements\User;
use craft\enums\PropagationMethod;
use craft\errors\InvalidElementException;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\Revisions;
use craft\test\TestCase;
use crafttests\fixtures\NemFieldLayoutFixture;
use crafttests\fixtures\NemSectionFixture;
use crafttests\fixtures\SitesFixture;
use crafttests\fixtures\UserFixture;
use UnitTester;

/**
 * Regression tests for historical NestedElementManager bugfixes.
 *
 * Each test guards a specific, previously-shipped bug in how Matrix/Address nested elements are
 * saved, duplicated, propagated across sites, or carried through drafts/revisions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NestedElementManagerTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected Elements $elements;
    protected Drafts $drafts;
    protected Revisions $revisions;

    /**
     * @inheritdoc
     */
    public function _fixtures(): array
    {
        return [
            'sites' => ['class' => SitesFixture::class],
            'nemFieldLayout' => ['class' => NemFieldLayoutFixture::class],
            'nemSection' => ['class' => NemSectionFixture::class],
            'users' => ['class' => UserFixture::class],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $this->elements = Craft::$app->getElements();
        $this->drafts = Craft::$app->getDrafts();
        $this->revisions = Craft::$app->getRevisions();
    }

    /**
     * Builds (and optionally saves) an owner entry for the NEM test section/entry type.
     *
     * @param array $matrixValue Raw serialized `nemMatrix` value (`craft\fields\Matrix`'s legacy flat format).
     * @param array $attributes Additional attributes to set on the entry.
     */
    private function _makeOwner(array $matrixValue = [], array $attributes = []): Entry
    {
        $entry = new Entry(array_merge([
            'sectionId' => 3000,
            'typeId' => 3000,
            'title' => 'NEM Owner ' . mt_rand(),
            'nemMatrix' => $matrixValue,
        ], $attributes));

        if (!$this->elements->saveElement($entry)) {
            throw new InvalidElementException($entry);
        }

        return $entry;
    }

    /**
     * @return Entry[]
     */
    private function _nestedEntries(Entry $owner): array
    {
        return Entry::find()
            ->ownerId($owner->id)
            ->siteId($owner->siteId)
            ->status(null)
            ->orderBy(['elements_owners.sortOrder' => SORT_ASC])
            ->all();
    }

    // Fixed by c242290 "Fix error": getIsTranslatable() used to call renderObjectTemplate() even
    // when propagationKeyFormat was null, which threw when a Custom-propagation field/attribute
    // hadn't configured a key format yet. (Confirmed: reverting the fix throws a TypeError here.)
    public function testGetIsTranslatableWithCustomPropagationAndNoKeyFormat(): void
    {
        $manager = new NestedElementManager(
            Address::class,
            fn() => Address::find(),
            [
                'attribute' => 'addresses',
                'propagationMethod' => PropagationMethod::Custom,
                'propagationKeyFormat' => null,
            ],
        );

        $owner = User::find()->username('user1')->one();
        self::assertNotNull($owner);

        // Should not throw, and should report translatable since Custom !== All
        self::assertTrue($manager->getIsTranslatable($owner));
        self::assertTrue($manager->getIsTranslatable(null));
    }

    // Fixed #14494 / "Fixed a bug": getSearchKeywords() (and other read paths) didn't set the owner
    // on the fetched nested elements before using them, and the owner-comparison in
    // setOwnerOnNestedElements() was comparing the element to itself instead of to $owner. (Exercises
    // the same code path as those fixes; reverting them didn't reproduce a failure here in this
    // single-level setup, so treat this as general coverage rather than a proven catch of that exact bug.)
    public function testSearchKeywordsIncludeNestedElementContent(): void
    {
        $owner = $this->_makeOwner([
            'new1' => [
                'type' => 'nemBlock',
                'fields' => ['nemText' => 'FindThisUniqueString'],
            ],
        ]);

        $field = $owner->getFieldLayout()->getFieldByHandle('nemMatrix');
        $keywords = $field->getSearchKeywords($owner->getFieldValue('nemMatrix'), $owner);

        self::assertStringContainsString('FindThisUniqueString', $keywords);
    }

    // Fixed last-block deletion (#14303) / 10069e2: `!$query->getCachedResult()` treated an empty
    // (but already-fetched) array as "not fetched yet", so deleting the last nested element and
    // resaving would silently re-query and resurrect it. (Confirmed: reverting to the falsy check
    // makes this test fail.)
    public function testDeletingLastNestedElementStaysDeleted(): void
    {
        $owner = $this->_makeOwner([
            'new1' => [
                'type' => 'nemBlock',
                'fields' => ['nemText' => 'Only block'],
            ],
        ]);
        self::assertCount(1, $this->_nestedEntries($owner));

        // Remove the only block
        $owner->setFieldValue('nemMatrix', []);
        if (!$this->elements->saveElement($owner)) {
            throw new InvalidElementException($owner);
        }
        self::assertCount(0, $this->_nestedEntries($owner));

        // Resave again with no changes - the deleted block must NOT come back
        $owner2 = Entry::find()->id($owner->id)->one();
        $owner2->setFieldValue('nemMatrix', []);
        if (!$this->elements->saveElement($owner2)) {
            throw new InvalidElementException($owner2);
        }
        self::assertCount(0, $this->_nestedEntries($owner2));
    }

    // Fixed #18453 / 966f484: new nested elements had `resaving` copied from the owner, which
    // suppressed NestedElementTrait::saveOwnership() and silently dropped their ownership row.
    // (Confirmed: reverting the fix drops the new block's ownership row.)
    public function testNewNestedElementSurvivesResavingOwner(): void
    {
        $owner = $this->_makeOwner([
            'new1' => [
                'type' => 'nemBlock',
                'fields' => ['nemText' => 'Original block'],
            ],
        ]);

        $originalNestedId = $this->_nestedEntries($owner)[0]->id;

        // Simulate a resave (as craft\services\Elements::resaveElements() would set) while also
        // adding a brand new, not-yet-saved nested block.
        $owner->resaving = true;
        $owner->setFieldValue('nemMatrix', [
            $originalNestedId => ['fields' => ['nemText' => 'Original block']],
            'new2' => ['type' => 'nemBlock', 'fields' => ['nemText' => 'Added during resave']],
        ]);
        if (!$this->elements->saveElement($owner)) {
            throw new InvalidElementException($owner);
        }

        $nested = $this->_nestedEntries($owner);
        self::assertCount(2, $nested);
        $added = array_values(array_filter($nested, fn(Entry $e) => $e->getFieldValue('nemText') === 'Added during resave'));
        self::assertCount(1, $added);

        // And the new element's ownership row must actually exist in the DB (not just be
        // reachable through an in-memory reference) - this is what `resaving` used to suppress.
        $ownershipRowExists = (new \craft\db\Query())
            ->from(Table::ELEMENTS_OWNERS)
            ->where(['elementId' => $added[0]->id, 'ownerId' => $owner->id])
            ->exists();
        self::assertTrue($ownershipRowExists);
    }

    // Fixed #14111 / e5d6cd8: duplicating an owner element didn't carry the nested elements'
    // sortOrder over, so duplicated Matrix content lost its original ordering. Also covers 3b0ede5's
    // "save as new" (duplicate-as-unpublished-draft) path. (sortOrder is now also preserved via the
    // normal element clone, so reverting e5d6cd8's line alone no longer reproduces a failure here -
    // treat this as general "save as new" duplication coverage.)
    public function testDuplicateEntryPreservesNestedElementSortOrder(): void
    {
        $owner = $this->_makeOwner([
            'new1' => ['type' => 'nemBlock', 'fields' => ['nemText' => 'First']],
            'new2' => ['type' => 'nemBlock', 'fields' => ['nemText' => 'Second']],
            'new3' => ['type' => 'nemBlock', 'fields' => ['nemText' => 'Third']],
        ]);

        $duplicate = $this->elements->duplicateElement($owner, [], true, true);
        $nested = $this->_nestedEntries($duplicate);

        self::assertCount(3, $nested);
        self::assertSame(['First', 'Second', 'Third'], array_map(fn(Entry $e) => $e->getFieldValue('nemText'), $nested));

        $this->elements->deleteElement($duplicate, true);
    }

    // Fixed #17630 / 82839a0: duplicateNestedElements() would choke on nested elements that don't
    // have an ID yet (e.g. still-unsaved elements sitting in an in-memory collection). (Confirmed:
    // reverting the fix throws "Attempting to duplicate an unsaved element.")
    public function testDuplicateNestedElementsIgnoresUnsavedElementsWithoutId(): void
    {
        $owner = $this->_makeOwner([
            'new1' => ['type' => 'nemBlock', 'fields' => ['nemText' => 'Saved block']],
        ]);

        $unsaved = new Entry([
            'typeId' => 3001,
            'fieldId' => $owner->getFieldLayout()->getFieldByHandle('nemMatrix')->id,
        ]);
        self::assertNull($unsaved->id);

        // Seed the field's query with a cached result that includes the unsaved element, the way
        // an in-progress (not-yet-submitted) Matrix edit would leave it.
        $query = $owner->getFieldValue('nemMatrix');
        $query->setCachedResult([...$this->_nestedEntries($owner), $unsaved]);

        $duplicate = $this->elements->duplicateElement($owner);
        $nested = $this->_nestedEntries($duplicate);

        self::assertCount(1, $nested);
        self::assertSame('Saved block', $nested[0]->getFieldValue('nemText'));

        $this->elements->deleteElement($duplicate);
    }

    // Models the #18461 bug report (2726404, 9022194, ea90a03, 3b0ede5, 2344e21): repeatedly
    // editing a nested element inside a draft, then applying the draft, used to churn the nested
    // element's canonical ID instead of keeping it stable. (Reproduces the reported scenario
    // end-to-end and passes against current code; reverting the individual canonicalId/guard lines
    // I could identify from those commits didn't reproduce a failure in this exact setup, so this is
    // best read as a realistic regression scenario for the bug class rather than a proven catch of
    // one specific line - the interaction is deep enough that isolating it further was out of scope.)
    public function testEditingDraftNestedElementRepeatedlyThenApplyingKeepsSameCanonicalId(): void
    {
        // A 2-level-nested structure (owner -> blockA -> blockB), matching the original bug
        // report: Matrix nested inside a Matrix block.
        $owner = $this->_makeOwner([
            'new1' => [
                'type' => 'nemBlock',
                'fields' => [
                    'nemText' => 'A',
                    'nemInnerMatrix' => [
                        'new1' => ['type' => 'nemBlock', 'fields' => ['nemText' => 'v1']],
                    ],
                ],
            ],
        ]);
        $blockA = $this->_nestedEntries($owner)[0];
        $originalNestedId = $this->_nestedEntries($blockA)[0]->id;

        /** @var Entry $draft */
        $draft = $this->drafts->createDraft($owner, 1);
        $draftBlockA = $this->_nestedEntries($draft)[0];

        $draftBlockB = $this->_nestedEntries($draftBlockA)[0];
        $draftBlockA->setFieldValue('nemInnerMatrix', [
            $draftBlockB->id => ['fields' => ['nemText' => 'v2']],
        ]);
        if (!$this->elements->saveElement($draftBlockA)) {
            throw new InvalidElementException($draftBlockA);
        }

        $draftBlockB = $this->_nestedEntries($draftBlockA)[0];
        $draftBlockA->setFieldValue('nemInnerMatrix', [
            $draftBlockB->id => ['fields' => ['nemText' => 'v3']],
        ]);
        if (!$this->elements->saveElement($draftBlockA)) {
            throw new InvalidElementException($draftBlockA);
        }

        $this->drafts->applyDraft($draft);

        $owner = Entry::find()->id($owner->id)->one();
        $blockA = $this->_nestedEntries($owner)[0];
        $finalNested = $this->_nestedEntries($blockA);

        self::assertCount(1, $finalNested);
        self::assertSame($originalNestedId, $finalNested[0]->id);
        self::assertSame('v3', $finalNested[0]->getFieldValue('nemText'));
    }

    // Models "fixes an issue where nested content could be lost or overwritten when adding new
    // site" (e2e4698) and d39f1f9: with a Matrix field whose propagation method is more restrictive
    // than its owner, adding a new site to the owner must duplicate the nested elements into that
    // site rather than losing/overwriting content. (e2e4698's specific fix - `in_array($siteId,
    // $newSiteIds)` instead of `!empty($newSiteIds)` - only diverges when a 3rd, unrelated site is
    // also in play; with just one new site added, both are equivalent, so this test currently
    // verifies the overall behavior but doesn't isolate that one line.)
    public function testSavingOwnerWithNewSiteDuplicatesNestedElementsWithoutLosingContent(): void
    {
        $owner = $this->_makeOwner([
            'new1' => ['type' => 'nemBlock', 'fields' => ['nemText' => 'Default site content']],
        ], ['siteId' => 1]);

        self::assertCount(1, $this->_nestedEntries($owner));

        // Simulate the owner becoming available in a new site (as ElementHelper::supportedSitesForElement()
        // + Elements::propagateElement() would trigger via $owner->newSiteIds during propagation)
        $ownerInNewSite = Entry::find()->id($owner->id)->siteId(1000)->status(null)->one();
        self::assertNotNull($ownerInNewSite, 'Owner should have propagated to the new site');

        $ownerInNewSite->newSiteIds = [1000];
        if (!$this->elements->saveElement($ownerInNewSite)) {
            throw new InvalidElementException($ownerInNewSite);
        }

        // Original site content must still be intact
        $defaultSiteNested = $this->_nestedEntries(Entry::find()->id($owner->id)->siteId(1)->status(null)->one());
        self::assertCount(1, $defaultSiteNested);
        self::assertSame('Default site content', $defaultSiteNested[0]->getFieldValue('nemText'));

        // New site must have received its own duplicated copy, not be empty
        $newSiteNested = $this->_nestedEntries(Entry::find()->id($owner->id)->siteId(1000)->status(null)->one());
        self::assertCount(1, $newSiteNested);
        self::assertSame('Default site content', $newSiteNested[0]->getFieldValue('nemText'));
        self::assertNotSame($defaultSiteNested[0]->id, $newSiteNested[0]->id);
    }

    // Models the scenario behind "Avoid internal server error - SQLSTATE Integrity constraint
    // violation on elements_owners" (a11e6d1), "Fix nested element deletion bug when restoring a
    // revision" (#18950 / 5f0dff9), and "Fix another nested element deletion bug" (011db82):
    // reverting to an old revision whose nested element ownership row might already exist must not
    // throw, and must restore the nested content. (Verifiably exercises revert-to-revision +
    // nested-element restoration end-to-end; reverting a11e6d1's specific upsert-vs-insert line
    // didn't reproduce a constraint violation in this exact setup, so treat this as general coverage
    // of the revert-to-revision path rather than a proven catch of that one line.)
    public function testRestoringOldRevisionRestoresNestedElementWithoutIntegrityError(): void
    {
        $owner = $this->_makeOwner([
            'new1' => ['type' => 'nemBlock', 'fields' => ['nemText' => 'Revision 1 content']],
        ]);

        sleep(1);
        $v1 = Entry::find()
            ->revisionOf($owner)
            ->siteId($owner->siteId)
            ->status(null)
            ->orderBy(['num' => SORT_DESC])
            ->one();
        self::assertNotNull($v1);

        // Remove the nested block and save again, creating a second revision without it
        $owner->setFieldValue('nemMatrix', []);
        if (!$this->elements->saveElement($owner)) {
            throw new InvalidElementException($owner);
        }
        self::assertCount(0, $this->_nestedEntries($owner));

        // Reverting to the first revision must not throw, and must bring the nested block back
        $this->revisions->revertToRevision($v1, 1);

        $owner = Entry::find()->id($owner->id)->one();
        $restoredNested = $this->_nestedEntries($owner);
        self::assertCount(1, $restoredNested);
        self::assertSame('Revision 1 content', $restoredNested[0]->getFieldValue('nemText'));
    }
}
