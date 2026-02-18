<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Database\Expressions\JsonExtract;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use yii\base\InvalidConfigException;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Generated field condition rule.
 *
 * @property ElementConditionInterface $condition
 *
 * @method ElementConditionInterface getCondition()
 */
class GeneratedFieldConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    public string $fieldUid;

    private array|false $field;

    #[\Override]
    public function getConfig(): array
    {
        return [
            ...parent::getConfig(),
            'fieldUid' => $this->fieldUid,
        ];
    }

    public function getLabel(): string
    {
        $field = $this->getFieldConfig();
        if (! $field) {
            throw new InvalidConfigException("Invalid generated field UUID: $this->fieldUid");
        }

        return $field['name'];
    }

    public function getGroupLabel(): ?string
    {
        return t('Fields');
    }

    public function getExclusiveQueryParams(): array
    {
        $field = $this->getFieldConfig();
        if (! $field) {
            return [];
        }

        $handle = $field['handle'];
        if (is_array($handle)) {
            if (! isset($handle['value'])) {
                return [];
            }
            $handle = $handle['value'];
        }

        return [$handle];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $field = $this->getFieldConfig();
        if (! $field) {
            return;
        }

        $value = $this->paramValue();
        if ($value === null) {
            return;
        }

        $query->whereParam(new JsonExtract('elements_sites.content', [$field['uid']]), $value, caseInsensitive: true, columnType: Schema::TYPE_JSON);
    }

    public function matchElement(ElementInterface $element): bool
    {
        $field = $this->getFieldConfig();
        if (! $field) {
            return true;
        }
        $value = $element->getGeneratedFieldValues()[$field['handle']] ?? null;

        return $this->matchValue($value);
    }

    #[\Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'fieldUid' => ['nullable', 'uuid'],
        ]);
    }

    private function getFieldConfig(): ?array
    {
        if (isset($this->field)) {
            return $this->field ?: null;
        }

        $this->field = false;

        foreach ($this->getCondition()->getFieldLayouts() as $fieldLayout) {
            foreach ($fieldLayout->getGeneratedFields() as $field) {
                if ($field['uid'] !== $this->fieldUid) {
                    continue;
                }

                if (($field['name'] ?? '') !== '' && ($field['handle'] ?? '') !== '') {
                    $this->field = $field;
                }

                break 2;
            }
        }

        return $this->field ?: null;
    }
}
