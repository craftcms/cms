<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Database\Expressions\JsonExtract;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Query;
use Illuminate\Database\Query\Builder;
use Override;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * Generated field condition rule.
 *
 * @property ElementConditionInterface $condition
 *
 * @method ElementConditionInterface getCondition()
 *
 * @phpstan-import-type GeneratedField from FieldLayout
 */
class GeneratedFieldConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public string $fieldUid;

    /** @var GeneratedField|false */
    private array|false $field;

    /** @return array<string, mixed> */
    #[Override]
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
            throw new RuntimeException("Invalid generated field UUID: $this->fieldUid");
        }

        return $field['name'];
    }

    public function getGroupLabel(): ?string
    {
        return t('Fields');
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        $field = $this->getFieldConfig();
        if (! $field) {
            return;
        }

        $value = $this->paramValue();
        if ($value === null) {
            return;
        }

        $query->whereParam(new JsonExtract('elements_sites.content', [$field['uid']]), $value, caseInsensitive: true, columnType: Query::TYPE_JSON);
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

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'fieldUid' => ['nullable', 'uuid'],
        ]);
    }

    /** @return GeneratedField|null */
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
