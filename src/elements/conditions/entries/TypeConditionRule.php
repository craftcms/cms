<?php

namespace craft\elements\conditions\entries;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\models\Section;

/**
 * Entry type condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Entry Type');
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

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->_sections = Craft::$app->getSections()->getAllSections();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        if (array_key_exists('entryTypeUid', $values)) {
            $values['values'] = array_filter([$values['entryTypeUid']]);
            unset($values['entryTypeUid'], $values['sectionUid']);
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @return array
     */
    protected function options(): array
    {
        $options = [];
        foreach ($this->_sections as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $options[$entryType->uid] = sprintf('%s - %s', $section->name, $entryType->name);
            }
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $sections = Craft::$app->getSections();
        $query->typeId($this->paramValue(fn($uid) => $sections->getEntryTypeByUid($uid)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue((string)$element->getType()->uid);
    }
}
