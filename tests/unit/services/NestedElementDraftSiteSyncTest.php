<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\ContentBlock as ContentBlockElement;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\ContentBlock;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\services\Drafts;
use craft\services\Elements;
use craft\test\TestCase;
use crafttests\fixtures\SitesFixture;
use RuntimeException;
use yii\db\IntegrityException;

/**
 * Reproduction test for https://github.com/craftcms/cms/issues/19277
 *
 * Applying a draft whose owner entry has a nested `craft\fields\ContentBlock` element throws a
 * `yii\db\IntegrityException` (duplicate key on `elements_sites`) if the canonical entry gained a new
 * site while the draft's own copy of the nested block was already forked off (i.e. edited) for the
 * sites that existed at that time. See #17644 for the same class of bug at the top-level-entry level,
 * which this reproduces one level deeper for a nested element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NestedElementDraftSiteSyncTest extends TestCase
{
    protected Elements $elements;
    protected Drafts $drafts;
    private PlainText $blockSubField;
    private ContentBlock $contentBlockField;
    private EntryType $entryType;
    private Section $section;

    /**
     * @inheritdoc
     */
    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class,
            ],
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

        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        $this->blockSubField = new PlainText();
        $this->blockSubField->name = 'Block Text';
        $this->blockSubField->handle = 'blockText';
        if (!Craft::$app->getFields()->saveField($this->blockSubField)) {
            throw new RuntimeException('Could not save block subfield.');
        }

        $this->contentBlockField = new ContentBlock();
        $this->contentBlockField->name = 'Test Block';
        $this->contentBlockField->handle = 'testBlock';
        $this->contentBlockField->setFieldLayout(
            $this->_makeFieldLayout(ContentBlockElement::class, $this->blockSubField)
        );
        if (!Craft::$app->getFields()->saveField($this->contentBlockField)) {
            throw new RuntimeException('Could not save content block field.');
        }

        $this->entryType = new EntryType();
        $this->entryType->name = 'Test Block Entry Type';
        $this->entryType->handle = 'testBlockEntryType';
        $this->entryType->hasTitleField = true;
        $this->entryType->setFieldLayout(
            $this->_makeFieldLayout(Entry::class, $this->contentBlockField)
        );
        if (!Craft::$app->getEntries()->saveEntryType($this->entryType)) {
            throw new RuntimeException('Could not save entry type.');
        }

        $this->section = new Section();
        $this->section->name = 'Test Block Section';
        $this->section->handle = 'testBlockSection';
        $this->section->type = Section::TYPE_CHANNEL;
        $this->section->propagationMethod = \craft\enums\PropagationMethod::All;
        $this->section->setEntryTypes([$this->entryType]);
        $this->section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => $primarySiteId,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
        ]);
        if (!Craft::$app->getEntries()->saveSection($this->section)) {
            throw new RuntimeException('Could not save section.');
        }
    }

    /**
     * Builds a single-tab field layout containing a single custom field.
     *
     * @param class-string $type
     */
    private function _makeFieldLayout(string $type, \craft\base\FieldInterface $field): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => $type]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements([new CustomField($field)]);
        $fieldLayout->setTabs([$tab]);
        return $fieldLayout;
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->entryType);
        Craft::$app->getFields()->deleteField($this->contentBlockField);
        Craft::$app->getFields()->deleteField($this->blockSubField);
        parent::_after();
    }

    /**
     * Reproduces #19277: applying a draft after its canonical entry gained a new site (which the
     * draft's own derivative copy of a nested ContentBlock never caught up with) throws an
     * IntegrityException instead of updating the existing `elements_sites` row for that site.
     */
    public function testApplyDraftAfterCanonicalGainsSiteForNestedBlock(): void
    {
        $sitesService = Craft::$app->getSites();
        $primarySiteId = $sitesService->getPrimarySite()->id;
        $secondSiteId = (int)$sitesService->getSiteByHandle('testSite1')->id;

        // 1. Create the canonical entry, with the content block filled in, in the primary site only.
        $entry = new Entry();
        $entry->sectionId = $this->section->id;
        $entry->typeId = $this->entryType->id;
        $entry->siteId = $primarySiteId;
        $entry->title = 'Test Entry';
        $entry->setFieldValue('testBlock', [
            'fields' => ['blockText' => 'original'],
        ]);

        if (!$this->elements->saveElement($entry)) {
            throw new RuntimeException('Could not save entry: ' . implode(', ', $entry->getFirstErrors()));
        }

        // 2. Create a draft of the entry.
        /** @var Entry $draft */
        $draft = $this->drafts->createDraft($entry, 1, 'Test Draft');

        // 3. Edit the draft's content block content and save. This forces
        // NestedElementManager/ContentBlock to fork off a genuinely separate, draft-owned copy
        // of the nested block (for the sites the draft currently supports: primary only).
        $draft->setFieldValue('testBlock', [
            'fields' => ['blockText' => 'changed by draft'],
        ]);
        if (!$this->elements->saveElement($draft)) {
            throw new RuntimeException('Could not save draft: ' . implode(', ', $draft->getFirstErrors()));
        }

        // 4. Independently of the draft, add a second site to the section, and resave the canonical
        // entry so it (and its ContentBlock, via propagationMethod: All) propagates to that new site.
        $this->section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => $primarySiteId,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
            new Section_SiteSettings([
                'siteId' => $secondSiteId,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
        ]);
        if (!Craft::$app->getEntries()->saveSection($this->section)) {
            throw new RuntimeException('Could not update section site settings.');
        }

        /** @var Entry $canonicalEntry */
        $canonicalEntry = Entry::find()->id($entry->id)->siteId($primarySiteId)->status(null)->one();
        self::assertNotNull($canonicalEntry);
        if (!$this->elements->saveElement($canonicalEntry)) {
            throw new RuntimeException('Could not resave canonical entry for new site: ' . implode(', ', $canonicalEntry->getFirstErrors()));
        }

        // Sanity check: canonical's nested block now has a row for the new site...
        $canonicalBlockForNewSite = ContentBlockElement::find()
            ->fieldId($this->contentBlockField->id)
            ->ownerId($entry->id)
            ->siteId($secondSiteId)
            ->status(null)
            ->one();
        self::assertNotNull($canonicalBlockForNewSite, 'Expected the canonical ContentBlock to have propagated to the new site.');

        // ...while the draft's own (forked) copy of the block never caught up with the new site.
        $draftBlockForNewSite = ContentBlockElement::find()
            ->fieldId($this->contentBlockField->id)
            ->ownerId($draft->id)
            ->siteId($secondSiteId)
            ->drafts(null)
            ->status(null)
            ->one();
        self::assertNull($draftBlockForNewSite, 'Expected the draft\'s ContentBlock to NOT have a row for the new site yet (that\'s the drift being reproduced).');

        // 5. Apply the draft — this is where GH-19277 throws an IntegrityException on `elements_sites`.
        try {
            $this->drafts->applyDraft($draft);
        } catch (IntegrityException $e) {
            self::fail('Applying the draft threw an IntegrityException (reproducing GH-19277): ' . $e->getMessage());
        }
    }
}
