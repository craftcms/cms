<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use Illuminate\Contracts\Database\Query\Builder;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;

use function CraftCms\Cms\t;

class LinkFieldConditionRule extends TextFieldConditionRule
{
    private const string OPERATOR_TYPE = 'type';

    /**
     * @var string|null The selected link type
     */
    public ?string $linkType = null;

    #[\Override]
    protected function operators(): array
    {
        return [
            ...parent::operators(),
            self::OPERATOR_TYPE,
        ];
    }

    #[\Override]
    public function getConfig(): array
    {
        return [
            ...parent::getConfig(),
            'linkType' => $this->linkType,
        ];
    }

    #[\Override]
    protected function operatorLabel(string $operator): string
    {
        return match ($operator) {
            self::OPERATOR_TYPE => t('is of type'),
            default => parent::operatorLabel($operator),
        };
    }

    #[\Override]
    protected function inputHtml(): string
    {
        if ($this->operator !== self::OPERATOR_TYPE) {
            return parent::inputHtml();
        }

        /** @var Link $field */
        $field = $this->field();
        $linkTypeOptions = array_map(
            fn (BaseLinkType $linkType) => ['value' => $linkType::id(), 'label' => $linkType::displayName()],
            $field->getLinkTypes(),
        );

        return FormFields::selectHtml([
            'name' => 'linkType',
            'options' => $linkTypeOptions,
            'value' => $this->linkType,
        ]);
    }

    #[\Override]
    public function modifyQuery(Builder $query): void
    {
        if ($this->operator !== self::OPERATOR_TYPE) {
            parent::modifyQuery($query);

            return;
        }

        /** @phpstan-ignore-next-line */
        $valueSql = array_map(fn (Link $field) => $field->getValueSql('type'), $this->fieldInstances());

        $query->where(new Coalesce($valueSql), $this->linkType);
    }

    #[\Override]
    protected function matchFieldValue($value): bool
    {
        if (! $this->field() instanceof Link) {
            return true;
        }

        if ($this->operator === self::OPERATOR_TYPE) {
            /** @var LinkData|null $value */
            return $value?->getType() === $this->linkType;
        }

        return parent::matchFieldValue($value);
    }

    #[\Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'linkType' => ['nullable', 'string'],
        ]);
    }
}
