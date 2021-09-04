<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
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
class EntryTypeConditionRule extends BaseConditionRule implements ElementQueryConditionRuleInterface
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

    public string $sectionHandle = '';
    public string $entryTypeHandle = '';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->_sections = Craft::$app->getSections()->getAllSections();

        // Set a default section
        if (!$this->sectionHandle) {
            $this->sectionHandle = ArrayHelper::firstValue($this->_sections)->handle;
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
            'sectionHandle' => $this->sectionHandle,
            'entryTypeHandle' => $this->entryTypeHandle,
        ]);
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

    /**
     * @return string
     */
    public function getInputHtml(): string
    {
        $inputAttributes = ['hx-post' => UrlHelper::actionUrl('conditions/render')];

        $html = Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'name' => 'sectionHandle',
            'value' => $this->sectionHandle,
            'options' => $this->getSectionOptions(),
            'inputAttributes' => $inputAttributes,
        ]);

        $this->_ensureEntryType();

        $html .= Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'name' => 'entryTypeHandle',
            'value' => $this->entryTypeHandle,
            'options' => $this->getEntryTypeOptions(),
            'inputAttributes' => $inputAttributes,
        ]);

        return $html;
    }

    /**
     *
     */
    private function _ensureEntryType(): void
    {
        if (!$this->entryTypeHandle || !ArrayHelper::keyExists($this->entryTypeHandle, $this->getEntryTypeOptions())) {
            $this->entryTypeHandle = ArrayHelper::firstKey($this->getEntryTypeOptions());
        }
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        /** @var EntryQuery $query */
        return $query
            ->section($this->sectionHandle)
            ->type($this->entryTypeHandle);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['sectionHandle', 'entryTypeHandle'], 'safe'],
        ]);
    }
}
