<?php

namespace craft\fields\conditions;

use Craft;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use yii\base\InvalidConfigException;
use yii\db\Schema;

/**
 * Generated field condition rule.
 *
 * @property ElementConditionInterface $condition
 * @method ElementConditionInterface getCondition()
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class GeneratedFieldConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    /**
     * The generated field’s UUID.
     */
    public string $fieldUid;

    private array|false $field;

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return [
            ...parent::getConfig(),
            'fieldUid' => $this->fieldUid,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        $field = $this->getFieldConfig();
        if (!$field) {
            throw new InvalidConfigException("Invalid generated field UUID: $this->fieldUid");
        }
        return $field['name'];
    }

    /**
     * @inheritdoc
     */
    public function getGroupLabel(): ?string
    {
        return Craft::t('app', 'Fields');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        $field = $this->getFieldConfig();
        if (!$field) {
            return [];
        }

        $handle = $field['handle'];
        if (is_array($handle)) {
            if (!isset($handle['value'])) {
                return [];
            }
            $handle = $handle['value'];
        }
        return [$handle];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $field = $this->getFieldConfig();
        if (!$field) {
            return;
        }

        $value = $this->paramValue();
        if ($value === null) {
            return;
        }

        $qb = Craft::$app->getDb()->getQueryBuilder();
        $valueSql = $qb->jsonExtract('elements_sites.content', [$field['uid']]);
        $query->andWhere(Db::parseParam($valueSql, $value, caseInsensitive: true, columnType: Schema::TYPE_JSON));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        $field = $this->getFieldConfig();
        if (!$field) {
            return true;
        }
        $value = $element->getGeneratedFieldValues()[$field['handle']] ?? null;
        return $this->matchValue($value);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            ['fieldUid', 'safe'],
        ]);
    }

    private function getFieldConfig(): ?array
    {
        if (!isset($this->field)) {
            $this->field = false;
            foreach ($this->getCondition()->getFieldLayouts() as $fieldLayout) {
                foreach ($fieldLayout->getGeneratedFields() as $field) {
                    if ($field['uid'] === $this->fieldUid) {
                        if (($field['name'] ?? '') !== '' && ($field['handle'] ?? '') !== '') {
                            $this->field = $field;
                        }
                        break 2;
                    }
                }
            }
        }

        return $this->field ?: null;
    }
}
