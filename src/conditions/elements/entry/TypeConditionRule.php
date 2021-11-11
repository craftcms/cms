<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\db\Table;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\models\Section;
use yii\db\QueryInterface;

/**
 * Entry type condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TypeConditionRule extends BaseConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Type');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['type', 'typeId'];
    }

    /**
     * @var Section[]
     */
    private array $_sections = [];

    public string $sectionUid = '';
    public string $entryTypeUid = '';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->_sections = Craft::$app->getSections()->getAllSections();

        // Set a default section
        if (!$this->sectionUid) {
            $this->sectionUid = ArrayHelper::firstValue($this->_sections)->uid;
        }

        // Once we have a section, set a default entry type
        $this->_ensureEntryType();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'sectionUid' => $this->sectionUid,
            'entryTypeUid' => $this->entryTypeUid,
        ]);
    }

    /**
     * @return array
     */
    private function _sectionOptions(): array
    {
        return ArrayHelper::map($this->_sections, 'uid', 'name');
    }

    /**
     * @return array
     */
    private function _entryTypeOptions(): array
    {
        $options = [];
        foreach ($this->_sections as $section) {
            if ($section->uid == $this->sectionUid) {
                foreach ($section->entryTypes as $entryType) {
                    $options[$entryType->uid] = $entryType->name;
                }
            }
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        $html = Cp::selectHtml([
            'name' => 'sectionUid',
            'value' => $this->sectionUid,
            'options' => $this->_sectionOptions(),
            'inputAttributes' => [
                'hx' => [
                    'post' => UrlHelper::actionUrl('conditions/render'), // Only the section re-renders the body
                    'target' => 'closest .rule-body',
                    'select' => '#' . Craft::$app->getView()->namespaceInputId('rule-body'),
                    'swap' => 'outerHTML',
                ],
            ],
        ]);

        $this->_ensureEntryType();

        $html .= Cp::selectHtml([
            'name' => 'entryTypeUid',
            'value' => $this->entryTypeUid,
            'options' => $this->_entryTypeOptions(),
        ]);

        return $html;
    }

    /**
     * Ensures an entry type is set correctly based on the section selected.
     */
    private function _ensureEntryType(): void
    {
        if (!$this->entryTypeUid || !ArrayHelper::keyExists($this->entryTypeUid, $this->_entryTypeOptions())) {
            $this->entryTypeUid = ArrayHelper::firstKey($this->_entryTypeOptions());
        }
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $sectionService = Craft::$app->getSections();
        $section = $sectionService->getSectionByUid($this->sectionUid);
        $typeId = Db::idByUid(Table::ENTRYTYPES, $this->entryTypeUid);
        $type = $sectionService->getEntryTypeById($typeId);

        /** @var EntryQuery $query */
        $query->type($type);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['sectionUid', 'entryTypeUid'], 'safe'],
        ]);
    }
}
