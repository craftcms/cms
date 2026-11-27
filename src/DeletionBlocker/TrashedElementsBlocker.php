<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\DeletionBlockers\Concerns\ListsElementChips;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Typecast;

use function CraftCms\Cms\t;

/**
 * Reports the elements that a deletion will move to the trash.
 *
 * @since 6.0.0
 */
class TrashedElementsBlocker extends BaseResolvableDeletionBlocker
{
    use ListsElementChips;

    #[\Override]
    public bool $expandDetails = false;

    private int $count;

    /**
     * @param  class-string<ElementInterface>  $elementType  The element type, such as {@see Entry}
     * @param  array<string, mixed>  $criteria  Element query criteria that select the elements, such as `['sectionId' => 1]`
     * @param  (Closure(int): string)|null  $summary  Returns the blocker’s summary for the number of elements, such as
     *                                                “The section contains 4 entries.” Defaults to a summary that says
     *                                                they’ll be moved to the trash.
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected readonly string $elementType,
        protected readonly array $criteria,
        protected readonly ?Closure $summary = null,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getKey(): string
    {
        return 'elements';
    }

    public function getSummary(): string
    {
        if ($this->summary !== null) {
            return ($this->summary)($this->count());
        }

        return t('{num, number} {num, plural, =1{{type}} other{{pluralType}}} will be moved to the trash.', [
            'num' => $this->count(),
            'type' => $this->elementType::lowerDisplayName(),
            'pluralType' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    public function getResolutionLabel(): string
    {
        return t('Move {num, plural, =1{{type}} other{{pluralType}}} to the trash', [
            'num' => $this->count(),
            'type' => $this->elementType::lowerDisplayName(),
            'pluralType' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }

    public function getDetails(): ?string
    {
        return $this->elementChipsHtml($this->query());
    }

    protected function getResolvedMessage(): string
    {
        return t('The {num, plural, =1{{type}} other{{pluralType}}} will be moved to the trash.', [
            'num' => $this->count(),
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
        if ($this->count() === 0) {
            return [];
        }

        return [
            [
                'label' => $this->elementType::pluralDisplayName(),
                'count' => $this->count(),
                'description' => $this->getSummary(),
            ],
        ];
    }

    private function count(): int
    {
        return $this->count ??= $this->query()->count();
    }

    /**
     * Matches what the deletion trashes: canonical elements and unpublished drafts.
     */
    private function query(): ElementQueryInterface
    {
        $query = $this->elementType::find();
        Typecast::configure($query, $this->criteria);

        return $query
            ->drafts(null)
            ->draftOf(false)
            ->whereNull('elements.canonicalId')
            ->site('*')
            ->unique()
            ->status(null);
    }
}
