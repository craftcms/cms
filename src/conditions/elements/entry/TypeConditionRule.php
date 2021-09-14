<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\db\Table;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\models\Section;
use yii\db\QueryInterface;

/**
 * Entry type condition rule.
 *
 * @property-read array $entryTypeOptions
 * @property-read array $sectionOptions
 * @property-read string $inputHtml
 * @property-read array $inputAttributes
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TypeConditionRule extends BaseConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Type');
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
    public function getSectionOptions(): array
    {
        return ArrayHelper::map($this->_sections, 'uid', 'name');
    }

    /**
     * @return array
     */
    public function getEntryTypeOptions(): array
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
     * Returns the input attributes.
     *
     * @return array
     */
    protected function getInputAttributes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        $html = Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'name' => 'sectionUid',
            'value' => $this->sectionUid,
            'options' => $this->getSectionOptions(),
            'inputAttributes' => array_merge($this->getInputAttributes(),[
                'hx-post' => UrlHelper::actionUrl('conditions/render'), // Only the section re-renders the body
                'hx-target' => 'closest .rule-body',
                'hx-select' => $this->getRuleBodyId(),
                'hx-swap' => 'outerHTML'
            ])
        ]);

        $this->_ensureEntryType();

        $html .= Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'name' => 'entryTypeUid',
            'value' => $this->entryTypeUid,
            'options' => $this->getEntryTypeOptions(),
            'inputAttributes' => $this->getInputAttributes(),
        ]);

        return $html;
    }

    /**
     * Ensures an entry type is set correctly based on the section selected.
     *
     * @return void
     */
    private function _ensureEntryType(): void
    {
        if (!$this->entryTypeUid || !ArrayHelper::keyExists($this->entryTypeUid, $this->getEntryTypeOptions())) {
            $this->entryTypeUid = ArrayHelper::firstKey($this->getEntryTypeOptions());
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
        $query->section($section)->type($type);
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
