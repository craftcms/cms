<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Condition\BaseElementSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

class RelatedToConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @var class-string<ElementInterface>
     */
    public string $elementType = Entry::class;

    public array $elementIds {
        get => $this->getElementIds();
        set {
            $this->setElementIds($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Related To');
    }

    /**
     * {@inheritdoc}
     */
    protected function elementType(): string
    {
        return $this->elementType;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function allowMultiple(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function elementSelectConfig(): array
    {
        return array_merge(parent::elementSelectConfig(), [
            'showSiteMenu' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $elementIds = $this->getElementIds();
        if (! empty($elementIds)) {
            $query->andRelatedTo($elementIds);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function inputHtml(): string
    {
        $id = 'element-type';

        return Html::hiddenLabel($this->getLabel(), $id).
            Html::tag('div',
                Cp::selectHtml([
                    'id' => $id,
                    'name' => 'elementType',
                    'options' => $this->_elementTypeOptions(),
                    'value' => $this->elementType,
                    'inputAttributes' => [
                        'hx' => [
                            'post' => UrlHelper::actionUrl('conditions/render'),
                        ],
                    ],
                ]).
                parent::inputHtml(),
                [
                    'class' => ['flex', 'flex-start'],
                ]
            );
    }

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
            'elementType' => ['required', 'string'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'elementType' => $this->elementType,
        ]);
    }

    /**
     * {@inheritdoc}
     */
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
