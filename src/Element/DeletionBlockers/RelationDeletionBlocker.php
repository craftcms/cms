<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\DeletionBlockers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class RelationDeletionBlocker extends BaseDeletionBlocker
{
    /**
     * @var array<string, mixed>
     *
     * @deprecated in 6.0.0. The details list the related elements as chips rather than in an element index.
     */
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
        return $this->elementChipsHtml($this->sourceElementType::find()
            ->relatedTo([
                'targetElement' => $this->elements->ids()->all(),
            ])
            ->site('*')
            ->unique()
            ->status(null));
    }

    /** @return list<array<string, mixed>> */
    public function getActions(): array
    {
        /** @var class-string<ElementInterface> $targetElementType */
        $targetElementType = $this->elements->first()::class;
        $numTargets = $this->elements->count();
        $replaceLabel = t('Replace {numRelations, plural, =1{relation} other{relations}}', [
            'numRelations' => $this->relationCount,
        ]);

        return [
            [
                'icon' => 'swap',
                'label' => $replaceLabel,
                'callback' => Html::jsWithVars(fn (
                    $title,
                    $targetElementType,
                    $targetIds,
                    $hardDelete,
                    $sourceElementType,
                ) => <<<JS
new Craft.CpModal('delete-elements/replace-relations-modal', {
  title: $title,
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
                    $replaceLabel,
                    $targetElementType,
                    $this->elements->ids()->all(),
                    $this->hardDelete,
                    $this->sourceElementType,
                ]),
            ],
            [
                'icon' => 'xmark-large',
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
