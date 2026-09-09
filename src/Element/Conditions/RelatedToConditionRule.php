<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseElementSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\ElementSelect;
use CraftCms\Cms\Form\Nodes\Field;
use Illuminate\Database\Query\Builder;

use function CraftCms\Cms\t;

class RelatedToConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    /**
     * @var class-string<ElementInterface>
     */
    public string $elementType = Entry::class;

    /** @var int[] */
    public array $elementIds {
        get => $this->getElementIds();
        set {
            $this->setElementIds($value);
        }
    }

    public function getLabel(): string
    {
        return t('Related To');
    }

    protected function elementType(): string
    {
        return $this->elementType;
    }

    #[\Override]
    protected function allowMultiple(): bool
    {
        return true;
    }

    #[\Override]
    protected function elementSelect(): ElementSelect
    {
        return parent::elementSelect()->showSiteMenu();
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        ElementQuery::applyRelatedTo($query, $this->getElementIds(), $elementQuery);
    }

    /** @return list<Node> */
    #[\Override]
    protected function inputNodes(): array
    {
        return [
            Field::make(t('Element Type'), Choice::make('elementType')
                ->options($this->_elementTypeOptions())
                ->withoutPlaceholder()
                ->value($this->elementType)
                ->reactive()),
            ...parent::inputNodes(),
        ];
    }

    /** @return array<int, array{value: class-string<ElementInterface>, label: string}> */
    private function _elementTypeOptions(): array
    {
        return app(Fields::class)->getRelationalFieldTypes()->map(function (string $field) {
            /** @var class-string<BaseRelationField> $field */
            $elementType = $field::elementType();

            return [
                'value' => $elementType,
                'label' => $elementType::displayName(),
            ];
        })->all();
    }

    #[\Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'elementType' => ['required', 'string', new ElementTypeRule],
        ]);
    }

    #[\Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'elementType' => $this->elementType,
        ]);
    }

    public function matchElement(ElementInterface $element): bool
    {
        $elementIds = $this->getElementIds();
        if (empty($elementIds)) {
            return true;
        }

        return $element::find()
            ->id($element->id ?: false)
            ->site('*')
            ->drafts($element->getIsDraft())
            ->provisionalDrafts($element->isProvisionalDraft)
            ->revisions($element->getIsRevision())
            ->status(null)
            ->relatedTo($elementIds)
            ->exists();
    }
}
