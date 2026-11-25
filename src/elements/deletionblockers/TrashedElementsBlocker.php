<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;

/**
 * TrashedElementsBlocker reports the elements that a deletion will move to the trash.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class TrashedElementsBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * @inheritdoc
     */
    public bool $expandDetails = false;

    private int $count;

    /**
     * Constructor
     *
     * @param class-string<ElementInterface> $elementType The element type, such as `craft\elements\Entry`
     * @param array $criteria Element query criteria that select the elements, such as `['sectionId' => 1]`
     */
    public function __construct(
        protected readonly string $elementType,
        protected readonly array $criteria,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function init(): void
    {
        // Match what the deletion trashes: canonical elements and unpublished drafts
        $this->count = Craft::configure($this->elementType::find(), $this->criteria)
            ->drafts(null)
            ->draftOf(false)
            ->andWhere(['elements.canonicalId' => null])
            ->site('*')
            ->unique()
            ->status(null)
            ->count();

        parent::init();
    }

    public function getKey(): string
    {
        return 'elements';
    }

    public function getSummary(): string
    {
        return Craft::t('app', '{num, number} {num, plural, =1{{type}} other{{pluralType}}} will be moved to the trash.', [
            'num' => $this->count,
            'type' => $this->elementType::lowerDisplayName(),
            'pluralType' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    public function getResolutionLabel(): string
    {
        return Craft::t('app', 'Move {num, plural, =1{{type}} other{{pluralType}}} to the trash', [
            'num' => $this->count,
            'type' => $this->elementType::lowerDisplayName(),
            'pluralType' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    public function getDetails(): ?string
    {
        return Cp::elementIndexHtml($this->elementType, [
            'context' => 'pane',
            'sources' => false,
            'jsSettings' => [
                'criteria' => [
                    ...$this->criteria,
                    'drafts' => null,
                    'draftOf' => false,
                    'savedDraftsOnly' => false,
                    'status' => null,
                ],
            ],
        ]);
    }

    protected function getResolvedMessage(): string
    {
        return Craft::t('app', 'The {num, plural, =1{{type}} other{{pluralType}}} will be moved to the trash.', [
            'num' => $this->count,
            'type' => $this->elementType::lowerDisplayName(),
            'pluralType' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    protected function isDestructive(): bool
    {
        return true;
    }

    protected function defineItems(): array
    {
        if ($this->count === 0) {
            return [];
        }

        return [
            [
                'label' => $this->elementType::pluralDisplayName(),
                'count' => $this->count,
                'description' => $this->getSummary(),
            ],
        ];
    }
}
