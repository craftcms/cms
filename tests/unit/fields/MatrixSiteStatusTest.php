<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\fields;

use Craft;
use craft\base\Element;
use craft\base\FieldInterface;
use craft\controllers\MatrixController;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\PropagationMethod;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\queue\jobs\ApplyNewPropagationMethod;
use craft\services\Entries;
use craft\services\Fields;
use craft\test\TestCase;
use craft\web\View;
use crafttests\fixtures\SitesFixture;
use Symfony\Component\DomCrawler\Crawler;
use yii\queue\sync\Queue;

/**
 * Regression coverage for the Craft 5 backport of craftcms/cms#19686.
 */
class MatrixSiteStatusTest extends TestCase
{
    private PlainText $textField;
    private Matrix $matrixField;
    private EntryType $blockType;
    private EntryType $ownerType;
    private Section $section;
    private int $primarySiteId;
    private int $otherSiteId;

    public function _fixtures(): array
    {
        return ['sites' => ['class' => SitesFixture::class]];
    }

    protected function _before(): void
    {
        parent::_before();
        $this->primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;
        $this->otherSiteId = Craft::$app->getSites()->getSiteByHandle('testSite2')->id;

        $this->textField = new PlainText(['name' => 'Status Text', 'handle' => 'statusText', 'translationMethod' => 'site']);
        self::assertTrue(Craft::$app->getFields()->saveField($this->textField));

        $this->blockType = new EntryType([
            'name' => 'Status Block',
            'handle' => 'statusBlock',
            'hasTitleField' => false,
            'titleFormat' => '{id}',
        ]);
        $this->blockType->setFieldLayout($this->layout($this->textField, true));
        self::assertTrue(Craft::$app->getEntries()->saveEntryType($this->blockType));

        $this->matrixField = new Matrix([
            'name' => 'Status Matrix',
            'handle' => 'statusMatrix',
            'viewMode' => Matrix::VIEW_MODE_BLOCKS,
            'propagationMethod' => PropagationMethod::All,
        ]);
        $this->matrixField->setEntryTypes([$this->blockType]);
        self::assertTrue(Craft::$app->getFields()->saveField($this->matrixField));

        $this->ownerType = new EntryType(['name' => 'Status Owner', 'handle' => 'statusOwner']);
        $this->ownerType->setFieldLayout($this->layout($this->matrixField));
        self::assertTrue(Craft::$app->getEntries()->saveEntryType($this->ownerType));

        $this->section = new Section([
            'name' => 'Status Owners',
            'handle' => 'statusOwners',
            'type' => Section::TYPE_CHANNEL,
            'propagationMethod' => PropagationMethod::All,
        ]);
        $this->section->setEntryTypes([$this->ownerType]);
        $this->section->setSiteSettings(array_map(fn(int $siteId) => new Section_SiteSettings([
            'siteId' => $siteId,
            'enabledByDefault' => true,
            'hasUrls' => false,
        ]), [$this->primarySiteId, $this->otherSiteId]));
        self::assertTrue(Craft::$app->getEntries()->saveSection($this->section));
    }

    protected function _after(): void
    {
        Craft::$app->getRequest()->setIsLivePreview(false);
        Craft::$app->getRequest()->setIsCpRequest(null);
        Craft::$app->getRequest()->setIsConsoleRequest(true);
        Craft::$app->getUser()->setIdentity(null);
        Craft::$app->getEntries()->deleteSection($this->section);
        Craft::$app->getEntries()->deleteEntryType($this->ownerType);
        Craft::$app->getFields()->deleteField($this->matrixField);
        Craft::$app->getEntries()->deleteEntryType($this->blockType);
        Craft::$app->getFields()->deleteField($this->textField);
        parent::_after();
    }

    public function testPostedSiteStatusDoesNotChangeGlobalOrOtherSiteStatus(): void
    {
        $owner = $this->createOwner();
        $this->postBlock($owner, ['enabledForSite' => '']);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));

        $block = $this->block($this->reload($owner));
        self::assertTrue($block->enabled);
        self::assertFalse($block->getEnabledForSite());

        $otherOwner = $this->reload($owner, $this->otherSiteId);
        self::assertTrue($this->block($otherOwner)->getEnabledForSite());
        // Editing the other translation must not re-enable this one.
        $this->postBlock($otherOwner, ['fields' => ['statusText' => 'Other translation']]);
        self::assertTrue(Craft::$app->getElements()->saveElement($otherOwner));
        self::assertFalse($this->block($this->reload($owner))->getEnabledForSite());

        $owner = $this->reload($owner);
        $this->postBlock($owner, ['enabledForSite' => '1']);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));
        self::assertTrue($this->block($this->reload($owner))->getEnabledForSite());
    }

    public function testGlobalStatusRemainsIndependentOfSiteStatus(): void
    {
        $owner = $this->createOwner();
        $this->postBlock($owner, ['enabled' => '', 'enabledForSite' => '']);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));
        self::assertFalse($this->block($this->reload($owner, $this->otherSiteId))->enabled);

        $owner = $this->reload($owner);
        $this->postBlock($owner, ['enabled' => '1']);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));
        self::assertTrue($this->block($this->reload($owner))->enabled);
        self::assertFalse($this->block($this->reload($owner))->getEnabledForSite());
        self::assertTrue($this->block($this->reload($owner, $this->otherSiteId))->getEnabledForSite());
    }

    public function testNewSiteDisabledBlockIsPropagatedWithOtherSiteEnabled(): void
    {
        $owner = $this->createOwner();
        $existingBlock = $this->block($owner);
        $uid = StringHelper::UUID();
        $owner->setFieldValueFromRequest('statusMatrix', [
            'entries' => ["uid:$uid" => [
                'type' => 'statusBlock',
                'enabled' => '1',
                'enabledForSite' => '',
                'fresh' => '1',
                'fields' => ['statusText' => 'New translation'],
            ]],
            'sortOrder' => [$existingBlock->uid, $uid],
        ]);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));

        foreach ([$this->primarySiteId, $this->otherSiteId] as $siteId) {
            $blocks = $this->reload($owner, $siteId)->getFieldValue('statusMatrix')->status(null)->all();
            self::assertCount(2, $blocks);
            self::assertSame($uid, $blocks[1]->uid);
            self::assertTrue($blocks[1]->enabled);
            self::assertSame($siteId === $this->otherSiteId, $blocks[1]->getEnabledForSite());
        }
    }

    public function testNewCollapsedBlockIsRememberedOnlyForTheEditedSite(): void
    {
        $owner = $this->reload($this->createOwner(), $this->otherSiteId);
        $existingBlock = $this->block($owner);
        $uid = StringHelper::UUID();
        $session = Craft::$app->getSession();
        $session->getJsFlashes();
        Craft::$app->getRequest()->setIsConsoleRequest(false);
        Craft::$app->getRequest()->setIsCpRequest(true);
        $owner->setFieldValueFromRequest('statusMatrix', [
            'entries' => ["uid:$uid" => [
                'type' => 'statusBlock',
                'enabled' => '1',
                'enabledForSite' => '',
                'collapsed' => '1',
                'fields' => ['statusText' => 'New translation'],
            ]],
            'sortOrder' => [$existingBlock->uid, $uid],
        ]);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));
        $blocks = $this->reload($owner)->getFieldValue('statusMatrix')->status(null)->all();
        self::assertCount(2, $blocks);
        $blockId = $blocks[1]->id;
        $collapseScripts = array_values(array_filter(
            array_column($session->getJsFlashes(), 0),
            fn(string $script) => str_contains($script, 'Craft.MatrixInput.rememberCollapsedEntryId('),
        ));
        self::assertSame([
            "Craft.MatrixInput.rememberCollapsedEntryId($blockId, $this->otherSiteId);",
        ], $collapseScripts);
        $session->getAssetBundleFlashes();
    }

    public function testSerializationPreservesSiteStatusAndOlderPostsLeaveItAlone(): void
    {
        $owner = $this->createOwner();
        $this->postBlock($owner, ['enabledForSite' => '']);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));
        $owner = $this->reload($owner);
        $block = $this->block($owner);
        $value = $owner->getFieldValue('statusMatrix');

        foreach ([
            $this->matrixField->serializeValue($value, $owner),
            $this->matrixField->serializeValueForDb($value, $owner),
        ] as $serialized) {
            self::assertTrue($serialized[$block->id]['enabled']);
            self::assertFalse($serialized[$block->id]['enabledForSite']);
        }

        $this->postBlock($owner, ['enabled' => '1']);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));
        self::assertFalse($this->block($this->reload($owner))->getEnabledForSite());
    }

    public function testDraftSiteStatusIsIsolatedUntilPublished(): void
    {
        $owner = $this->createOwner();
        /** @var Entry $draft */
        $draft = Craft::$app->getDrafts()->createDraft($owner, null);
        $this->postBlock($draft, ['enabledForSite' => '']);
        self::assertTrue(Craft::$app->getElements()->saveElement($draft));
        self::assertFalse($this->block($this->reload($draft))->getEnabledForSite());
        self::assertTrue($this->block($this->reload($owner))->getEnabledForSite());
        self::assertTrue($this->block($this->reload($owner, $this->otherSiteId))->getEnabledForSite());

        Craft::$app->getDrafts()->applyDraft($draft);
        self::assertFalse($this->block($this->reload($owner))->getEnabledForSite());
        self::assertTrue($this->block($this->reload($owner, $this->otherSiteId))->getEnabledForSite());
    }

    public function testIncompleteTranslationCanBeSavedWhenDisabledForSite(): void
    {
        $owner = $this->reload($this->createOwner(), $this->otherSiteId);
        $this->postBlock($owner, ['enabledForSite' => '', 'fields' => ['statusText' => '']]);
        $owner->setScenario(Element::SCENARIO_LIVE);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner), json_encode($owner->getErrors()));
        self::assertFalse($this->block($this->reload($owner))->getEnabledForSite());
        self::assertTrue($this->block($this->reload($owner, $this->primarySiteId))->getEnabledForSite());
    }

    public function testIncompleteTranslationCannotBeReenabled(): void
    {
        $owner = $this->reload($this->createOwner(), $this->otherSiteId);
        $this->postBlock($owner, ['enabledForSite' => '', 'fields' => ['statusText' => '']]);
        $owner->setScenario(Element::SCENARIO_LIVE);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner));

        $owner = $this->reload($owner);
        $this->postBlock($owner, ['enabledForSite' => '1']);
        $owner->setScenario(Element::SCENARIO_LIVE);
        self::assertFalse(Craft::$app->getElements()->saveElement($owner));
        self::assertTrue($owner->hasErrors());
        self::assertFalse($this->block($this->reload($owner))->getEnabledForSite());
    }

    public function testPreviewExcludesSiteDisabledBlocks(): void
    {
        $owner = $this->createOwner();
        $block = $this->block($owner);
        Craft::$app->getRequest()->setIsConsoleRequest(false);
        Craft::$app->getRequest()->setIsLivePreview(true);
        $query = $this->matrixField->normalizeValue([
            'entries' => [$block->uid => ['type' => 'statusBlock', 'enabled' => '1', 'enabledForSite' => '']],
            'sortOrder' => [$block->uid],
        ], $owner);
        self::assertCount(0, $query->all());
    }

    public function testBlockMenuAndIndicatorDistinguishSiteAndGlobalStatus(): void
    {
        $block = $this->block($this->createOwner());
        $siteName = $block->getSite()->name;
        $html = new Crawler($this->renderBlock($block));
        self::assertContains("Disable for $siteName", $this->visibleActions($html));
        self::assertContains('Disable globally', $this->visibleActions($html));

        $block->setEnabledForSite(false);
        $html = new Crawler($this->renderBlock($block));
        self::assertCount(1, $html->filter('.matrixblock.disabled-entry[data-disabled-site]:not([data-disabled-global])'));
        self::assertContains("Enable for $siteName", $this->visibleActions($html));
        self::assertSame("Disabled for $siteName", $html->filter('.status.off')->attr('title'));
        self::assertSame('', $html->filter('input[name$="[enabledForSite]"]')->attr('value'));

        $block->enabled = false;
        $html = new Crawler($this->renderBlock($block));
        self::assertContains('Enable globally', $this->visibleActions($html));
        self::assertNotContains("Enable for $siteName", $this->visibleActions($html));
        self::assertSame('Disabled globally', $html->filter('.status.off .visually-hidden')->text());
    }

    public function testSingleSiteBlocksRetainSimpleStatusActions(): void
    {
        $this->section->setSiteSettings([$this->section->getSiteSettings()[$this->primarySiteId]]);
        self::assertTrue(Craft::$app->getEntries()->saveSection($this->section));
        $html = new Crawler($this->renderBlock($this->block($this->createOwner())));
        self::assertContains('Disable', $this->visibleActions($html));
        self::assertNotContains('Disable globally', $this->visibleActions($html));
        self::assertCount(0, $html->filter('button[data-action="disableForSite"]'));
    }

    /**
     * @dataProvider singleSitePropagationTransitions
     */
    public function testPropagationTransitionNormalizesSingleSiteStatus(PropagationMethod $method, bool $disableOtherSite): void
    {
        $owner = $this->createOwner();
        $disabledSiteId = $disableOtherSite ? $this->otherSiteId : $this->primarySiteId;
        $disabledOwner = $this->reload($owner, $disabledSiteId);
        $this->postBlock($disabledOwner, ['enabledForSite' => '']);
        self::assertTrue(Craft::$app->getElements()->saveElement($disabledOwner));
        $block = $this->block($this->reload($owner, $disabledSiteId));
        self::assertCount(2, $block->getSupportedSites());
        self::assertTrue($block->enabled);
        self::assertFalse($block->getEnabledForSite());

        $this->matrixField->propagationMethod = $method;
        self::assertTrue(Craft::$app->getFields()->saveField($this->matrixField));

        // Reload persisted field settings and layouts, as a new queue worker would.
        Craft::$app->set('fields', new Fields());
        Craft::$app->set('entries', new Entries());
        $block = $this->block($this->reload($owner, $disabledSiteId));
        self::assertSame($method, $block->getField()->propagationMethod);
        self::assertCount(1, $block->getSupportedSites());

        $job = new ApplyNewPropagationMethod([
            'elementType' => Entry::class,
            'criteria' => ['fieldId' => $this->matrixField->id],
        ]);
        $job->execute(new Queue());

        $blockIds = [];
        foreach ([$this->primarySiteId, $this->otherSiteId] as $siteId) {
            $block = $this->block($this->reload($owner, $siteId));
            $blockIds[] = $block->id;
            self::assertCount(1, $block->getSupportedSites());
            self::assertSame($siteId !== $disabledSiteId, $block->enabled);
            self::assertTrue($block->getEnabledForSite());
        }
        self::assertNotSame($blockIds[0], $blockIds[1]);

        $disabledOwner = $this->reload($owner, $disabledSiteId);
        $html = new Crawler($this->renderBlock($this->block($disabledOwner)));
        self::assertContains('Enable', $this->visibleActions($html));
        self::assertNotContains('Disable', $this->visibleActions($html));
        self::assertSame('1', $html->filter('input[name$="[enabledForSite]"]')->attr('value'));

        // The ordinary Enable action only needs to change the global flag.
        $this->postBlock($disabledOwner, ['enabled' => '1']);
        self::assertTrue(Craft::$app->getElements()->saveElement($disabledOwner));
        $block = $this->block($this->reload($owner, $disabledSiteId));
        self::assertTrue($block->enabled);
        self::assertTrue($block->getEnabledForSite());
        $otherSiteId = $disableOtherSite ? $this->primarySiteId : $this->otherSiteId;
        $otherBlock = $this->block($this->reload($owner, $otherSiteId));
        self::assertTrue($otherBlock->enabled);
        self::assertTrue($otherBlock->getEnabledForSite());
    }

    public static function singleSitePropagationTransitions(): array
    {
        return [
            'all to none, primary disabled' => [PropagationMethod::None, false],
            'all to none, secondary disabled' => [PropagationMethod::None, true],
            'all to language, primary disabled' => [PropagationMethod::Language, false],
            'all to language, secondary disabled' => [PropagationMethod::Language, true],
        ];
    }

    /**
     * @dataProvider singleSitePropagationMethods
     */
    public function testFieldPropagationDeterminesWhetherSiteActionsAreAvailable(PropagationMethod $method): void
    {
        $this->matrixField->propagationMethod = $method;
        self::assertTrue(Craft::$app->getFields()->saveField($this->matrixField));
        $block = $this->block($this->createOwner());
        self::assertCount(1, $block->getSupportedSites());
        $html = new Crawler($this->renderBlock($block));
        self::assertContains('Disable', $this->visibleActions($html));
        self::assertCount(0, $html->filter('button[data-action="disableForSite"]'));
    }

    public static function singleSitePropagationMethods(): array
    {
        return [
            'no propagation' => [PropagationMethod::None],
            'different languages' => [PropagationMethod::Language],
        ];
    }

    public function testDynamicallyRenderedBlocksIncludeEscapedSiteStatus(): void
    {
        $owner = $this->createOwner();
        $block = $this->block($owner);
        $block->getSite()->name = 'Primary <em>Site</em>';
        $block->setEnabledForSite(false);
        self::assertTrue(Craft::$app->getElements()->saveElement($block));
        Craft::$app->getUser()->setIdentity(new User(['id' => 1, 'admin' => true]));
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getRequest()->setBodyParams([
            'entryIds' => [$block->id],
            'siteId' => $owner->siteId,
            'namespace' => 'testNamespace',
        ]);
        $response = (new MatrixController('matrix', Craft::$app))->actionRenderBlocks();
        $html = new Crawler($response->data['blockHtml']);
        self::assertContains('Enable for Primary <em>Site</em>', $this->visibleActions($html));
        self::assertSame('Disabled for Primary <em>Site</em>', $html->filter('.status.off .visually-hidden')->text());
        self::assertCount(0, $html->filter('.status.off em'));
    }

    private function createOwner(): Entry
    {
        $owner = new Entry([
            'sectionId' => $this->section->id,
            'typeId' => $this->ownerType->id,
            'siteId' => $this->primarySiteId,
            'title' => 'Matrix status owner',
        ]);
        $owner->setFieldValue('statusMatrix', [
            'new1' => ['type' => 'statusBlock', 'fields' => ['statusText' => 'Ready translation']],
        ]);
        self::assertTrue(Craft::$app->getElements()->saveElement($owner), json_encode($owner->getErrors()));
        return $this->reload($owner);
    }

    private function postBlock(Entry $owner, array $data): void
    {
        $block = $this->block($owner);
        $owner->setFieldValueFromRequest('statusMatrix', [
            'entries' => [$block->uid => ['type' => 'statusBlock'] + $data],
            'sortOrder' => [$block->uid],
        ]);
    }

    private function reload(Entry $owner, ?int $siteId = null): Entry
    {
        $entry = Entry::find()->id($owner->id)->siteId($siteId ?? $owner->siteId)
            ->drafts($owner->getIsDraft())->status(null)->one();
        self::assertNotNull($entry);
        return $entry;
    }

    private function block(Entry $owner): Entry
    {
        $blocks = $owner->getFieldValue('statusMatrix')->status(null)->all();
        self::assertCount(1, $blocks);
        return $blocks[0];
    }

    private function renderBlock(Entry $block): string
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Matrix/block.twig', [
            'name' => 'statusMatrix',
            'entry' => $block,
            'entryTypes' => [$this->blockType],
            'currentUser' => new User(['admin' => false]),
        ], View::TEMPLATE_MODE_CP);
    }

    private function visibleActions(Crawler $html): array
    {
        return $html->filter('.menu li:not(.hidden) .menu-item-label')
            ->each(fn(Crawler $item) => trim($item->text()));
    }

    private function layout(FieldInterface $field, bool $required = false): FieldLayout
    {
        $layout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content']);
        $tab->setLayout($layout);
        $tab->setElements([new CustomField($field, ['required' => $required])]);
        $layout->setTabs([$tab]);
        return $layout;
    }
}
