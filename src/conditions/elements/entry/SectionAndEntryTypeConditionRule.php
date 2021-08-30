<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use yii\db\QueryInterface;

/**
 *
 * @property-read array $entryTypeOptions
 * @property-read array $sectionOptions
 * @property-read string $inputHtml
 * @property-read array $inputAttributes
 */
class SectionAndEntryTypeConditionRule extends BaseConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @var \craft\models\Section[]
     */
    private array $_sections = [];

    public string $sectionHandle = '';
    public string $entryTypeHandle = '';

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->_sections = Craft::$app->getSections()->getAllSections();

        if (!$this->sectionHandle) {
            $this->sectionHandle = ArrayHelper::firstValue($this->_sections)->handle;
        }

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        $config = parent::getConfig();
        $config['sectionHandle'] = $this->sectionHandle;
        $config['entryTypeHandle'] = $this->entryTypeHandle;

        return $config;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Section & Entry Type');
    }

    /**
     * @return array
     */
    public function getSectionOptions(): array
    {
        return ArrayHelper::map($this->_sections, 'handle', 'name');
    }

    public function getEntryTypeOptions(): array
    {
        $options = [];
        foreach ($this->_sections as $section) {
            if ($section->handle == $this->sectionHandle) {
                foreach ($section->entryTypes as $entryType) {
                    $options[$entryType->handle] = $entryType->name;
                }
            }
        }

        return $options;
    }

    protected function getInputAttributes(): array
    {
        return [
            'hx-post' => UrlHelper::actionUrl('conditions/render')
        ];
    }

    /**
     * @return string
     */
    public function getInputHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'name' => 'sectionHandle',
            'value' => $this->sectionHandle,
            'options' => $this->getSectionOptions(),
            'inputAttributes' => $this->getInputAttributes()
        ]);

        if ($this->sectionHandle) {

            if (!$this->entryTypeHandle || !ArrayHelper::keyExists($this->entryTypeHandle, $this->getEntryTypeOptions())) {
                $this->entryTypeHandle = ArrayHelper::firstKey($this->getEntryTypeOptions());
            }

            $html .= Craft::$app->getView()->renderTemplate('_includes/forms/select', [
                'name' => 'entryTypeHandle',
                'value' => $this->entryTypeHandle,
                'options' => $this->getEntryTypeOptions(),
                'inputAttributes' => $this->getInputAttributes()
            ]);
        }

        return $html;
    }

    /**
     * @inheritDoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        /** @var EntryQuery $query */
        return $query->section($this->sectionHandle)->type($this->entryTypeHandle);
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['sectionHandle', 'entryTypeHandle'], 'safe'];

        return $rules;
    }
}