<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\DeletionBlockers;

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

class RelationDeletionBlocker extends BaseDeletionBlocker
{
    /** @var array<string, mixed> */
    public array $elementIndexSettings = [];

    protected int $relationCount;

    /**
     * @param  class-string<ElementInterface>  $sourceElementType
     * @param  ElementCollection<int, covariant ElementInterface>  $elements
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected string $sourceElementType,
        ElementCollection $elements,
        bool $hardDelete,
        array $config = [],
    ) {
        parent::__construct($elements, $hardDelete, $config);

        $this->relationCount = $this->sourceElementType::find()
            ->relatedTo([
                'targetElement' => $this->elements->ids()->all(),
            ])
            ->site('*')
            ->unique()
            ->status(null)
            ->count();
    }

    public function isActive(): bool
    {
        return $this->relationCount !== 0;
    }

    public function getSummary(): string
    {
        /** @var class-string<ElementInterface> $targetElementType */
        $targetElementType = $this->elements->first()::class;

        return mb_ucfirst(t('The {numTargets, plural, =1{{targetTypeSingular} is} other{{targetTypePlural} are}} related by {numRelations, number} other {numRelations, plural, =1{{sourceTypeSingular}} other{{sourceTypePlural}}}.', [
            'sourceTypeSingular' => $this->sourceElementType::lowerDisplayName(),
            'sourceTypePlural' => $this->sourceElementType::pluralLowerDisplayName(),
            'targetTypeSingular' => $targetElementType::lowerDisplayName(),
            'targetTypePlural' => $targetElementType::pluralLowerDisplayName(),
            'numRelations' => $this->relationCount,
            'numTargets' => $this->elements->count(),
        ]));
    }

    public function getDetails(): ?string
    {
        return app(ElementIndexHtml::class)->html($this->sourceElementType, Arr::merge([
            'context' => 'pane',
            'sources' => false,
            'jsSettings' => [
                'criteria' => [
                    'relatedTo' => [
                        'targetElement' => $this->elements->ids()->all(),
                    ],
                    'status' => null,
                ],
            ],
        ], $this->elementIndexSettings));
    }

    /** @return list<array<string, mixed>> */
    public function getActions(): array
    {
        /** @var class-string<ElementInterface> $targetElementType */
        $targetElementType = $this->elements->first()::class;
        $numTargets = $this->elements->count();

        return [
            [
                'icon' => 'swap',
                'label' => t('Replace {numRelations, plural, =1{relation} other{relations}}', [
                    'numRelations' => $this->relationCount,
                ]),
                'callback' => Html::jsWithVars(fn (
                    $targetElementType,
                    $targetIds,
                    $hardDelete,
                    $sourceElementType,
                ) => <<<JS
new Craft.CpModal('delete-elements/replace-relations-modal', {
  params: {
    elementType: $targetElementType,
    elementIds: $targetIds,
    hardDelete: $hardDelete,
    sourceElementType: $sourceElementType,
  },
  onSubmit: (ev) => {
    resolve(ev.response.data.message);
  },
  onCancel: () => {
    reject();
  },
})
JS, [
                    $targetElementType,
                    $this->elements->ids()->all(),
                    $this->hardDelete,
                    $this->sourceElementType,
                ]),
            ],
            [
                'icon' => 'xmark',
                'label' => t('Remove {numRelations, plural, =1{relation} other{relations}}', [
                    'numRelations' => $this->relationCount,
                ]),
                'callback' => Html::jsWithVars(fn ($message) => "resolve($message);", [
                    t('The {numRelations, plural, =1{relation} other {relations}} will be removed once the {numTargets, plural, =1{{targetTypeSingular} is} other{{targetTypePlural} are}} deleted.', [
                        'targetTypeSingular' => $targetElementType::lowerDisplayName(),
                        'targetTypePlural' => $targetElementType::pluralLowerDisplayName(),
                        'numRelations' => $this->relationCount,
                        'numTargets' => $numTargets,
                    ]),
                ]),
            ],
        ];
    }
}
