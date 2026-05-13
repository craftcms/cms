<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\ElementCollection;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * Class RelationDeletionBlocker
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class RelationDeletionBlocker extends BaseDeletionBlocker
{
    public array $elementIndexSettings = [];
    protected int $relationCount;

    /**
     * Constructor
     *
     * @param class-string<ElementInterface> $sourceElementType
     */
    public function __construct(
        protected string $sourceElementType,
        ElementCollection $elements,
        bool $hardDelete,
        array $config = [],
    ) {
        parent::__construct($elements, $hardDelete, $config);
    }

    public function init()
    {
        $this->relationCount = $this->sourceElementType::find()
            ->relatedTo([
                'targetElement' => $this->elements->ids()->all(),
            ])
            ->site('*')
            ->unique()
            ->status(null)
            ->count();

        parent::init();
    }

    public function isActive(): bool
    {
        return $this->relationCount !== 0;
    }

    public function getSummary(): string
    {
        /** @var class-string<ElementInterface> $targetElementType */
        $targetElementType = $this->elements->first()::class;

        return Craft::t('app', 'The {numTargets, plural, =1{{targetTypeSingular} is} other{{targetTypePlural} are}} related by {numRelations, number} other {numRelations, plural, =1{{sourceTypeSingular}} other{{sourceTypePlural}}}.', [
            'sourceTypeSingular' => $this->sourceElementType::lowerDisplayName(),
            'sourceTypePlural' => $this->sourceElementType::pluralLowerDisplayName(),
            'targetTypeSingular' => $targetElementType::lowerDisplayName(),
            'targetTypePlural' => $targetElementType::pluralLowerDisplayName(),
            'numRelations' => $this->relationCount,
            'numTargets' => $this->elements->count(),
        ]);
    }

    public function getDetails(): ?string
    {
        return Cp::elementIndexHtml($this->sourceElementType, ArrayHelper::merge([
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

    public function getActions(): array
    {
        /** @var class-string<ElementInterface> $targetElementType */
        $targetElementType = $this->elements->first()::class;
        $numTargets = $this->elements->count();

        return [
            [
                'icon' => 'swap',
                'label' => Craft::t('app', 'Replace {numRelations, plural, =1{relation} other{relations}}', [
                    'numRelations' => $this->relationCount,
                ]),
                'callback' => Html::jsWithVars(fn(
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
});
JS, [
                    $targetElementType,
                    $this->elements->ids()->all(),
                    $this->hardDelete,
                    $this->sourceElementType,
                ]),
            ],
            [
                'icon' => 'xmark',
                'label' => Craft::t('app', 'Remove {numRelations, plural, =1{relation} other{relations}}', [
                    'numRelations' => $this->relationCount,
                ]),
                'callback' => Html::jsWithVars(fn($message) => "resolve($message);", [
                    Craft::t('app', 'The {numRelations, plural, =1{relation} other {relations}} will be removed once the {numTargets, plural, =1{{targetTypeSingular} is} other{{targetTypePlural} are}} deleted.', [
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
