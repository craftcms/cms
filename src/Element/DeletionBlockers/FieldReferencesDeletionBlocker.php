<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\DeletionBlockers;

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Field\FieldReferences;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

class FieldReferencesDeletionBlocker extends BaseDeletionBlocker
{
    private readonly int $referenceCount;

    /**
     * @param  ElementCollection<int, covariant ElementInterface>  $elements
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        ElementCollection $elements,
        bool $hardDelete,
        array $config = [],
    ) {
        parent::__construct($elements, $hardDelete, $config);

        $this->referenceCount = $this->fieldReferences()->referenceCountForTargets($this->elements->ids()->all());
    }

    public function isActive(): bool
    {
        return $this->referenceCount !== 0;
    }

    public function getSummary(): string
    {
        $targetElementType = $this->targetElementType();

        return t('The {numTargets, plural, =1{{targetTypeSingular} is} other{{targetTypePlural} are}} referenced by fields in {numSources, number} other {numSources, plural, =1{element} other{elements}}.', [
            'targetTypeSingular' => $targetElementType::lowerDisplayName(),
            'targetTypePlural' => $targetElementType::pluralLowerDisplayName(),
            'numSources' => $this->referenceCount,
            'numTargets' => $this->elements->count(),
        ]);
    }

    public function getDetails(): ?string
    {
        $groups = $this->fieldReferences()->referenceIdsByTypeForTargets($this->elements->ids()->all());

        if ($groups->isEmpty()) {
            return null;
        }

        return $groups
            ->map(function ($sourceIds, string $sourceElementType) {
                /** @var class-string<ElementInterface> $sourceElementType */
                return Html::tag('h3', $sourceElementType::pluralDisplayName()).
                    app(ElementIndexHtml::class)->html($sourceElementType, [
                        'context' => 'pane',
                        'sources' => false,
                        'jsSettings' => [
                            'criteria' => [
                                'id' => $sourceIds->all(),
                                'drafts' => null,
                                'provisionalDrafts' => null,
                                'revisions' => false,
                                'status' => null,
                            ],
                        ],
                    ]);
            })
            ->join('');
    }

    /** @return list<array<string, mixed>> */
    public function getActions(): array
    {
        $targetElementType = $this->targetElementType();

        return [
            [
                'icon' => 'swap',
                'label' => t('Replace {numReferences, plural, =1{reference} other{references}}', [
                    'numReferences' => $this->referenceCount,
                ]),
                'callback' => Html::jsWithVars(fn (
                    $targetElementType,
                    $targetIds,
                    $hardDelete,
                ) => <<<JS
new Craft.CpModal('delete-elements/replace-references-modal', {
  params: {
    elementType: $targetElementType,
    elementIds: $targetIds,
    hardDelete: $hardDelete,
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
                ]),
            ],
            [
                'icon' => 'xmark',
                'label' => t('Ignore {numReferences, plural, =1{reference} other{references}}', [
                    'numReferences' => $this->referenceCount,
                ]),
                'callback' => 'resolve();',
            ],
        ];
    }

    /**
     * @return class-string<ElementInterface>
     */
    private function targetElementType(): string
    {
        return $this->elements->first()::class;
    }

    private function fieldReferences(): FieldReferences
    {
        return app(FieldReferences::class);
    }
}
