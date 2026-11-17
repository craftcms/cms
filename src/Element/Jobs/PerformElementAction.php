<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Jobs;

use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Queue\BatchedElementJob;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Database\Query\Builder;
use Override;
use RuntimeException;

use function CraftCms\Cms\craftAuth;

/**
 * Performs an element action on more elements than an element index performs
 * one on within a request, a batch of elements at a time, as the user who
 * chose it.
 *
 * @since 6.0.0
 */
class PerformElementAction extends BatchedElementJob
{
    /**
     * The IDs of the current batch's elements, which the action is performed
     * on together once the batch is gathered.
     *
     * @var list<int>
     */
    private array $batchIds = [];

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array{type: class-string<ElementActionInterface>, ...}  $action  The action's config.
     * @param  list<int>  $elementIds  The elements the action hasn't been performed on yet.
     * @param  array<string, mixed>  $criteria  Criteria the element query needs to find the elements, such as their site or `trashed`.
     * @param  int  $userId  The user the action is performed as.
     */
    public function __construct(
        protected string $elementType,
        public array $action,
        public array $elementIds,
        protected array $criteria,
        public int $userId,
        protected ?string $description = null,
    ) {
        parent::__construct();

        $this->batchSize = ElementActions::SYNC_LIMIT;
    }

    #[Override]
    public function handle(): void
    {
        $guard = craftAuth();
        // A sync queue runs the job within the request, whose user comes back after
        $previousUser = $guard->user();

        if (! $guard instanceof StatefulGuard || ! $guard->onceUsingId($this->userId)) {
            throw new RuntimeException("Couldn’t perform the element action as user {$this->userId}.");
        }

        try {
            parent::handle();
        } finally {
            if ($previousUser !== null) {
                $guard->setUser($previousUser);
            } elseif (method_exists($guard, 'forgetUser')) {
                $guard->forgetUser();
            }
        }
    }

    #[Override]
    protected function getQuery(): Builder
    {
        $query = parent::getQuery();
        assert($query instanceof ElementQueryInterface);

        return $query->id($this->elementIds);
    }

    /**
     * An action can take elements out of the query, such as by deleting them,
     * so each batch queries only the elements still to be acted on, from the
     * start, rather than skipping those already processed.
     */
    #[Override]
    protected function queryOffset(): int
    {
        return -$this->itemOffset;
    }

    protected function processElement(ElementInterface $element): void
    {
        $this->batchIds[] = (int) $element->id;
    }

    #[Override]
    protected function afterBatch(): void
    {
        $ids = $this->batchIds;
        $this->batchIds = [];

        // The elements still to be acted on are gone, so there's nothing left
        // to do, rather than another empty batch to spawn.
        if ($ids === []) {
            $this->itemOffset = $this->totalItems();

            return;
        }

        $query = parent::getQuery();
        assert($query instanceof ElementQueryInterface);

        $elementActions = app(ElementActions::class);
        $elementActions->invoke(
            $elementActions->createAction($this->action, $this->elementType),
            $query->id($ids),
        );

        $this->elementIds = array_values(array_diff($this->elementIds, $ids));
    }

    #[Override]
    protected function defaultDescription(): string
    {
        return I18N::prep('Performing an action on {type}', [
            'type' => $this->elementType::pluralLowerDisplayName(),
        ]);
    }
}
