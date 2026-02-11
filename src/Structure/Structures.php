<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Structure\Data\Structure;
use CraftCms\Cms\Structure\Enums\Action;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Events\ElementInserted;
use CraftCms\Cms\Structure\Events\ElementUpdated;
use CraftCms\Cms\Structure\Events\InsertingElement;
use CraftCms\Cms\Structure\Events\UpdatingElement;
use CraftCms\Cms\Structure\Models\Structure as StructureModel;
use CraftCms\Cms\Structure\Models\StructureElement as StructureElementModel;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;
use yii\base\Exception;

#[Singleton]
final class Structures
{
    /**
     * @var int The timeout to pass to [[\yii\mutex\Mutex::acquire()]] when acquiring a lock on the structure.
     */
    public int $mutexTimeout = 3;

    /**
     * @var StructureElementModel[]
     */
    private array $rootElementRecordsByStructureId = [];

    // Structure CRUD
    // -------------------------------------------------------------------------

    public function getStructureById(int $structureId, bool $withTrashed = false): ?Structure
    {
        $result = DB::table(Table::STRUCTURES)
            ->select([
                'id',
                'maxLevels',
                'uid',
            ])
            ->unless(
                $withTrashed,
                fn (Builder $query) => $query->whereNull('dateDeleted'),
            )
            ->find($structureId);

        return $result ? new Structure($result) : null;
    }

    public function getStructureByUid(string $structureUid, bool $withTrashed = false): ?Structure
    {
        $result = DB::table(Table::STRUCTURES)
            ->select([
                'id',
                'maxLevels',
                'uid',
            ])
            ->where('uid', $structureUid)
            ->unless(
                $withTrashed,
                fn (Builder $query) => $query->whereNull('dateDeleted'),
            )
            ->first();

        return $result ? new Structure($result) : null;
    }

    /**
     * Patches an array of entries, filling in any gaps in the tree.
     *
     * @template T of ElementInterface
     *
     * @param  T[]  $elements
     */
    public function fillGapsInElements(array &$elements): void
    {
        /** @var ElementInterface|null $prevElement */
        $prevElement = null;
        $patchedElements = [];

        // https://github.com/craftcms/cms/issues/16085
        // don't assume that elements are in the top to bottom order
        usort($elements, fn (ElementInterface $a, ElementInterface $b) => $a->lft <=> $b->lft);

        foreach ($elements as $i => $element) {
            // Did we just skip any elements?
            if (
                $element->level !== 1 &&
                (
                    $i === 0 ||
                    (! $element->isSiblingOf($prevElement) && ! $element->isChildOf($prevElement))
                )
            ) {
                // Merge in any missing ancestors
                $ancestorQuery = $element::find()
                    ->structureId($element->structureId)
                    ->ancestorOf($element)
                    ->siteId($element->siteId)
                    ->status(null);

                if ($prevElement) {
                    $ancestorQuery->where('structureelements.lft', '>', $prevElement->lft);
                }

                /** @var T $ancestor */
                foreach ($ancestorQuery->all() as $ancestor) {
                    $patchedElements[] = $ancestor;
                }
            }

            $patchedElements[] = $element;
            $prevElement = $element;
        }

        $elements = $patchedElements;
    }

    /**
     * Filters an array of elements down to only <= X branches.
     *
     * @template T of ElementInterface
     *
     * @param  T[]  $elements
     */
    public function applyBranchLimitToElements(array &$elements, int $branchLimit): void
    {
        $branchCount = 0;
        $prevElement = null;

        foreach ($elements as $i => $element) {
            // Is this a new branch?
            if ($prevElement === null || ! $element->isDescendantOf($prevElement)) {
                $branchCount++;

                // Have we gone over?
                if ($branchCount > $branchLimit) {
                    array_splice($elements, $i);
                    break;
                }
            }

            $prevElement = $element;
        }
    }

    /**
     * Saves a structure
     *
     * @return bool Whether the structure was saved successfully
     */
    public function saveStructure(Structure $structure): bool
    {
        if ($structure->id) {
            $structureModel = StructureModel::query()
                ->withTrashed()
                ->findOrFail($structure->id);
        } else {
            $structureModel = new StructureModel;
        }

        $structureModel->maxLevels = $structure->maxLevels;
        $structureModel->uid = $structure->uid;
        $structureModel->dateDeleted = null;

        if ($success = $structureModel->save()) {
            $structure->id = $structureModel->id;
        }

        return $success;
    }

    public function deleteStructureById(int $structureId): bool
    {
        if (! $structureId) {
            return false;
        }

        return (bool) DB::table(Table::STRUCTURES)->softDelete($structureId);
    }

    /**
     * Returns the descendant level delta for a given element.
     */
    public function getElementLevelDelta(int $structureId, ElementInterface $element): int
    {
        $structureElementModel = $this->getElementModel($structureId, $element);

        if ($structureElementModel === null) {
            return 0;
        }

        /** @var StructureElementModel|null $deepestDescendant */
        $deepestDescendant = $structureElementModel
            ->children()
            ->reorderDesc('level')
            ->first();

        if ($deepestDescendant) {
            return $deepestDescendant->level - $structureElementModel->level;
        }

        return 0;
    }

    // Moving elements around
    // -------------------------------------------------------------------------

    /**
     * Prepends an element to another within a given structure.
     */
    public function prepend(
        int $structureId,
        ElementInterface $element,
        ElementInterface|int $parentElement,
        Mode $mode = Mode::Auto,
    ): bool {
        $parentElementRecord = $this->getElementModel($structureId, $parentElement);

        if ($parentElementRecord === null) {
            throw new Exception('There was a problem getting the parent element.');
        }

        return $this->doIt($structureId, $element, $parentElementRecord, Action::Prepend, $mode);
    }

    /**
     * Appends an element to another within a given structure.
     */
    public function append(
        int $structureId,
        ElementInterface $element,
        ElementInterface|int $parentElement,
        Mode $mode = Mode::Auto,
    ): bool {
        $parentElementRecord = $this->getElementModel($structureId, $parentElement);

        if ($parentElementRecord === null) {
            throw new Exception('There was a problem getting the parent element.');
        }

        return $this->doIt($structureId, $element, $parentElementRecord, Action::Append, $mode);
    }

    /**
     * Prepends an element to the root of a given structure.
     */
    public function prependToRoot(int $structureId, ElementInterface $element, Mode $mode = Mode::Auto): bool
    {
        $parentElementRecord = $this->getRootElementRecord($structureId);

        return $this->doIt($structureId, $element, $parentElementRecord, Action::Prepend, $mode);
    }

    /**
     * Appends an element to the root of a given structure.
     */
    public function appendToRoot(int $structureId, ElementInterface $element, Mode $mode = Mode::Auto): bool
    {
        $parentElementRecord = $this->getRootElementRecord($structureId);

        return $this->doIt($structureId, $element, $parentElementRecord, Action::Append, $mode);
    }

    /**
     * Moves an element before another within a given structure.
     */
    public function moveBefore(
        int $structureId,
        ElementInterface $element,
        ElementInterface|int $nextElement,
        Mode $mode = Mode::Auto,
    ): bool {
        $nextElementRecord = $this->getElementModel($structureId, $nextElement);

        if ($nextElementRecord === null) {
            throw new Exception('There was a problem getting the next element.');
        }

        return $this->doIt($structureId, $element, $nextElementRecord, Action::PlaceBefore, $mode);
    }

    /**
     * Moves an element after another within a given structure.
     */
    public function moveAfter(
        int $structureId,
        ElementInterface $element,
        ElementInterface|int $prevElement,
        Mode $mode = Mode::Auto,
    ): bool {
        $prevElementRecord = $this->getElementModel($structureId, $prevElement);

        if ($prevElementRecord === null) {
            throw new Exception('There was a problem getting the previous element.');
        }

        return $this->doIt($structureId, $element, $prevElementRecord, Action::PlaceAfter, $mode);
    }

    /**
     * Removes an element from a given structure.
     */
    public function remove(int $structureId, ElementInterface $element): bool
    {
        $elementRecord = $this->getElementModel($structureId, $element);

        if ($elementRecord && ! $elementRecord->delete()) {
            return false;
        }

        $element->root = null;
        $element->lft = null;
        $element->rgt = null;
        $element->level = null;

        return true;
    }

    /**
     * Returns a structure element model from given structure and element IDs.
     */
    private function getElementModel(int $structureId, ElementInterface|int $element): ?StructureElementModel
    {
        $elementId = is_numeric($element) ? $element : $element->id;

        if ($elementId) {
            return StructureElementModel::query()
                ->where('structureId', $structureId)
                ->where('elementId', $elementId)
                ->first();
        }

        return null;
    }

    /**
     * Returns the root node for a given structure ID, or creates one if it doesn't exist.
     */
    private function getRootElementRecord(int $structureId): StructureElementModel
    {
        if (isset($this->rootElementRecordsByStructureId[$structureId])) {
            return $this->rootElementRecordsByStructureId[$structureId];
        }

        /** @var StructureElementModel|null $elementRecord */
        $elementRecord = StructureElementModel::query()
            ->where('structureId', $structureId)
            ->roots()
            ->first();

        if (! $elementRecord) {
            // Create it
            $elementRecord = new StructureElementModel([
                'structureId' => $structureId,
            ]);
            $elementRecord->makeRoot();
        }

        return $this->rootElementRecordsByStructureId[$structureId] = $elementRecord;
    }

    /**
     * Updates a ElementInterface with the new structure attributes from a StructureElement record.
     */
    private function doIt(
        int $structureId,
        ElementInterface $element,
        StructureElementModel $targetElementModel,
        Action $action,
        Mode $mode,
    ): bool {
        // Get a lock or bust
        $lockName = 'structure:'.$structureId;

        $lock = Cache::lock($lockName, 30);
        $lock->block($this->mutexTimeout);

        $structureElementModel = null;

        /** @var Element $element */
        // Figure out what we're doing
        if ($mode !== Mode::Insert) {
            // See if there's an existing structure element record
            $structureElementModel = $this->getElementModel($structureId, $element);

            if ($structureElementModel !== null) {
                $mode = Mode::Update;
            }
        }

        if ($structureElementModel === null) {
            $structureElementModel = new StructureElementModel([
                'structureId' => $structureId,
                'elementId' => $element->id,
            ]);

            $mode = Mode::Insert;
        }

        /** @var Mode::Insert|Mode::Update $mode */
        [$beforeEvent, $afterEvent] = match ($mode) {
            Mode::Insert => [InsertingElement::class, ElementInserted::class],
            Mode::Update => [UpdatingElement::class, ElementUpdated::class],
        };

        $targetElementId = $targetElementModel->isRoot() ? null : $targetElementModel->elementId;

        event($event = new $beforeEvent(
            element: $element,
            structureId: $structureId,
            targetElementId: $targetElementId,
            action: $action,
        ));

        if (! $event->isValid) {
            $lock->release();

            return false;
        }

        // Tell the element about it
        if (! $element->beforeMoveInStructure($structureId)) {
            $lock->release();

            return false;
        }

        $method = match ($action) {
            Action::Prepend => 'prependTo',
            Action::Append => 'appendTo',
            Action::PlaceBefore => 'insertBefore',
            Action::PlaceAfter => 'insertAfter',
        };

        DB::beginTransaction();
        try {
            if (! $structureElementModel->$method($targetElementModel)) {
                DB::rollBack();
                $lock->release();

                return false;
            }

            $lock->release();

            // Update the element with the latest values.
            $element->root = $structureElementModel->root;
            $element->lft = $structureElementModel->lft;
            $element->rgt = $structureElementModel->rgt;
            $element->level = $structureElementModel->level;

            // Tell the element about it
            $element->afterMoveInStructure($structureId);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $lock->release();
            throw $e;
        }

        // Invalidate all caches for the element type
        // (see https://github.com/craftcms/cms/issues/14846)
        Craft::$app->getElements()->invalidateCachesForElementType($element::class);

        event(new $afterEvent(
            element: $element,
            structureId: $structureId,
            targetElementId: $targetElementId,
            action: $action,
        ));

        return true;
    }
}
