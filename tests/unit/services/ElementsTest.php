<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\Entry;
use craft\enums\PropagationMethod;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\services\Elements;
use craft\test\TestCase;
use craft\test\TestSetup;
use crafttests\fixtures\AssetFixture;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\settings\GeneralConfigSettingFixture;
use crafttests\fixtures\SitesFixture;
use crafttests\fixtures\UserFixture;
use RuntimeException;

/**
 * Unit tests for the config service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Oliver Stark <os@fortrabbit.com>
 * @since 5.8
 */
class ElementsTest extends TestCase
{
    /**
     * @var Elements
     */
    public $elements;

    private PlainText $blockTextField;
    private Matrix $matrixField;
    private EntryType $blockEntryType;
    private EntryType $ownerEntryType;
    private Section $section;

    /**
     * @return void
     */
    public function testParseRefs(): void
    {
        // Generate a random slug that is unlikely to exist:
        $randomSlug = Craft::$app->getSecurity()->generateRandomString(10);

        $entryWithUrl = Entry::find()
            ->slug('With--URL--1')
            ->one();

        $strings = [
            // Things that should stay the same:
            'no-tags' => ['No tags here!', 'No tags here!'],
            'incomplete-closing' => ['Incomplete {tag.', 'Incomplete {tag.'],
            'incomplete-opening' => ['Incomplete tag}.', 'Incomplete tag}.'],
            'invalid-type-ref' => ['Invalid {beeble:1234:property}', 'Invalid {beeble:1234:property}'],
            'invalid-type-class' => ['Invalid {craft\elements\Beeble:1234:property}', 'Invalid {craft\elements\Beeble:1234:property}'],

            // Entries + behaviors
            'entry-default-property-id' => [TestSetup::SITE_URL . 'some-uri/With--URL--1', "{entry:$entryWithUrl->id}"],
            'entry-url' => [TestSetup::SITE_URL . 'some-uri/With--URL--1', "{entry:$entryWithUrl->id:url}"],
            'entry-title' => ['With URL 1', "{entry:$entryWithUrl->id:title}"],
            'entry-custom-identifer-slug' => ['With URL 1', '{entry:With--URL--1:title}'],
            'entry-custom-identifer-section-and-slug' => ['With URL 1', '{entry:withUri1/With--URL--1:title}'],
            'entry-custom-field' => ['foo', '{entry:test1/Theories--of--life:plainTextField}'],
            'entry-other-site-id' => ['Theories of life', '{entry:test1/Theories--of--life@1001:title}'],
            'entry-other-site-handle' => ['Theories of life', '{entry:test1/Theories--of--life@testSite2:title}'],
            'entry-other-site-uuid' => ['Theories of life', '{entry:test1/Theories--of--life@e9c6ae73-c175-4a3c-afa4-1ee095aa4b55:title}'],

            // Things that should use fallback text:
            'fallback-invalid-type' => ['Fallback text', '{beeble:bobbing:bubbles||Fallback text}'],
            'fallback-nonexistent-element-id' => ['Fallback text', '{entry:999999999||Fallback text}'],
            'fallback-nonexistent-element-custom' => ['Fallback text', "{entry:test1/$randomSlug||Fallback text}"],
            'fallback-nonexistent-property-id' => ['Fallback text', "{entry:999999999:propertyThatIsNotDefined||Fallback text}"],
            'fallback-nonexistent-property-custom' => ['Fallback text', "{entry:test1/$randomSlug:propertyThatIsNotDefined||Fallback text}"],

            // Recursive evaluation:
            'recursive-eval' => ['Substitution in A: [Substitution in B: [Value from C]]', '{entry:test1/recursive-reference-a:plainTextField}'],
        ];

        foreach ($strings as $label => [$expected, $text]) {
            self::assertEquals($expected, $this->elements->parseRefs($text), $label);
        }
    }

    /**
     * @inheritdoc
     */
    public function _fixtures(): array
    {
        return [
            'generalConfig:allowUppercaseInSlug' => [
                'class' => GeneralConfigSettingFixture::class,
                'setting' => 'allowUppercaseInSlug',
                'value' => true,
            ],
            // Address?
            'assets' => [
                'class' => AssetFixture::class,
            ],
            // Category?
            // ContentBlock?
            'entries' => [
                'class' => EntryFixture::class,
            ],
            'globalSet' => [
                'class' => GlobalSetFixture::class,
            ],
            // Tag?
            'users' => [
                'class' => UserFixture::class,
            ],
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

        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        // A single-site, non-versioned section with a Matrix field, built directly (rather than via
        // shared fixtures) so this test doesn't have to reason about revision-creation or multi-site
        // propagation - it only cares about reordering already-saved nested entries.
        $this->blockTextField = new PlainText();
        $this->blockTextField->name = 'Block Text';
        $this->blockTextField->handle = 'blockText';
        if (!Craft::$app->getFields()->saveField($this->blockTextField)) {
            throw new RuntimeException('Could not save block text field.');
        }

        $this->blockEntryType = new EntryType();
        $this->blockEntryType->name = 'Reorder Test Block';
        $this->blockEntryType->handle = 'reorderTestBlock';
        $this->blockEntryType->hasTitleField = false;
        $this->blockEntryType->titleFormat = '{id}';
        $this->blockEntryType->setFieldLayout($this->_makeFieldLayout(Entry::class, [$this->blockTextField]));
        if (!Craft::$app->getEntries()->saveEntryType($this->blockEntryType)) {
            throw new RuntimeException('Could not save block entry type.');
        }

        $this->matrixField = new Matrix();
        $this->matrixField->name = 'Reorder Test Matrix';
        $this->matrixField->handle = 'reorderTestMatrix';
        $this->matrixField->propagationMethod = PropagationMethod::None;
        $this->matrixField->setEntryTypes([$this->blockEntryType]);
        if (!Craft::$app->getFields()->saveField($this->matrixField)) {
            throw new RuntimeException('Could not save matrix field.');
        }

        $this->ownerEntryType = new EntryType();
        $this->ownerEntryType->name = 'Reorder Test Owner';
        $this->ownerEntryType->handle = 'reorderTestOwner';
        $this->ownerEntryType->hasTitleField = true;
        $this->ownerEntryType->setFieldLayout($this->_makeFieldLayout(Entry::class, [$this->matrixField], [new EntryTitleField()]));
        if (!Craft::$app->getEntries()->saveEntryType($this->ownerEntryType)) {
            throw new RuntimeException('Could not save owner entry type.');
        }

        $this->section = new Section();
        $this->section->name = 'Reorder Test Section';
        $this->section->handle = 'reorderTestSection';
        $this->section->type = Section::TYPE_CHANNEL;
        $this->section->enableVersioning = false;
        $this->section->propagationMethod = PropagationMethod::All;
        $this->section->setEntryTypes([$this->ownerEntryType]);
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
     * @inheritdoc
     */
    protected function _after(): void
    {
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->ownerEntryType);
        Craft::$app->getEntries()->deleteEntryType($this->blockEntryType);
        Craft::$app->getFields()->deleteField($this->matrixField);
        Craft::$app->getFields()->deleteField($this->blockTextField);
        parent::_after();
    }

    /**
     * Builds a single-tab field layout containing the given layout elements.
     *
     * @param class-string $type
     * @param \craft\base\FieldInterface[] $fields
     * @param \craft\base\FieldLayoutElement[] $leadingElements
     */
    private function _makeFieldLayout(string $type, array $fields, array $leadingElements = []): FieldLayout
    {
        $fieldLayout = new FieldLayout(['type' => $type]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($fieldLayout);
        $tab->setElements([
            ...$leadingElements,
            ...array_map(fn($field) => new CustomField($field), $fields),
        ]);
        $fieldLayout->setTabs([$tab]);
        return $fieldLayout;
    }

    // Covers #19321: reorderNestedElements() exposes the reordering logic that previously only lived
    // inline in NestedElementsController::actionReorder(), so it can be called without going through
    // a CP request.
    public function testReorderNestedElementsMovesElementToNewOffset(): void
    {
        $owner = new Entry();
        $owner->sectionId = $this->section->id;
        $owner->typeId = $this->ownerEntryType->id;
        $owner->title = 'Reorder Test Owner Entry';
        $owner->setFieldValue('reorderTestMatrix', [
            'new1' => ['type' => 'reorderTestBlock', 'fields' => ['blockText' => 'First']],
            'new2' => ['type' => 'reorderTestBlock', 'fields' => ['blockText' => 'Second']],
            'new3' => ['type' => 'reorderTestBlock', 'fields' => ['blockText' => 'Third']],
        ]);

        if (!$this->elements->saveElement($owner)) {
            throw new RuntimeException('Could not save owner entry: ' . implode(', ', $owner->getFirstErrors()));
        }

        $nested = Entry::find()
            ->ownerId($owner->id)
            ->siteId($owner->siteId)
            ->status(null)
            ->orderBy(['elements_owners.sortOrder' => SORT_ASC])
            ->all();
        self::assertSame(['First', 'Second', 'Third'], array_map(fn(Entry $e) => $e->getFieldValue('blockText'), $nested));

        // Move "Third" to the front
        $this->elements->reorderNestedElements($owner, $owner->getFieldValue('reorderTestMatrix'), [$nested[2]->id], 0);

        $reordered = Entry::find()
            ->ownerId($owner->id)
            ->siteId($owner->siteId)
            ->status(null)
            ->orderBy(['elements_owners.sortOrder' => SORT_ASC])
            ->all();
        self::assertSame(['Third', 'First', 'Second'], array_map(fn(Entry $e) => $e->getFieldValue('blockText'), $reordered));
    }
}
