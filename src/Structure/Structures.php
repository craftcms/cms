<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure;

use craft\base\Element;
use craft\base\ElementInterface;
use craft\models\Structure;
use craft\records\StructureElement;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Structure\Enums\Action;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Events\ElementInserted;
use CraftCms\Cms\Structure\Events\ElementMoved;
use CraftCms\Cms\Structure\Events\InsertingElement;
use CraftCms\Cms\Structure\Events\MovingElement;
use CraftCms\Cms\Structure\Models\Structure as StructureModel;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
     * @var StructureElement[]
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

        return $result ? new Structure((array) $result) : null;
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

        return $result ? new Structure((array) $result) : null;
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
                    $ancestorQuery->andWhere(['>', 'structureelements.lft', $prevElement->lft]);
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
        $elementRecord = $this->getElementRecord($structureId, $element);
        /** @var StructureElement|null $deepestDescendant */
        $deepestDescendant = $elementRecord
            ->children()
            ->orderBy(['level' => SORT_DESC])
            ->one();

        if ($deepestDescendant) {
            return $deepestDescendant->level - $elementRecord->level;
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
        $parentElementRecord = $this->getElementRecord($structureId, $parentElement);

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
        $parentElementRecord = $this->getElementRecord($structureId, $parentElement);

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
        $nextElementRecord = $this->getElementRecord($structureId, $nextElement);

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
        $prevElementRecord = $this->getElementRecord($structureId, $prevElement);

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
        $elementRecord = $this->getElementRecord($structureId, $element);

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
     * Returns a structure element record from given structure and element IDs.
     */
    private function getElementRecord(int $structureId, ElementInterface|int $element): ?StructureElement
    {
        $elementId = is_numeric($element) ? $element : $element->id;

        if ($elementId) {
            return StructureElement::findOne([
                'structureId' => $structureId,
                'elementId' => $elementId,
            ]);
        }

        return null;
    }

    /**
     * Returns the root node for a given structure ID, or creates one if it doesn't exist.
     */
    private function getRootElementRecord(int $structureId): StructureElement
    {
        if (isset($this->rootElementRecordsByStructureId[$structureId])) {
            return $this->rootElementRecordsByStructureId[$structureId];
        }

        /** @var StructureElement|null $elementRecord */
        $elementRecord = StructureElement::find()
            ->where(['structureId' => $structureId])
            ->roots()
            ->one();

        if (! $elementRecord) {
            // Create it
            $elementRecord = new StructureElement;
            $elementRecord->structureId = $structureId;
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
        StructureElement $targetElementRecord,
        Action $action,
        Mode $mode,
    ): bool {
        // Get a lock or bust
        $lockName = 'structure:'.$structureId;

        Cache::lock($lockName)->block($this->mutexTimeout);

        $elementRecord = null;

        /** @var Element $element */
        // Figure out what we're doing
        if ($mode !== Mode::Insert) {
            // See if there's an existing structure element record
            $elementRecord = $this->getElementRecord($structureId, $element);

            if ($elementRecord !== null) {
                $mode = Mode::Update;
            }
        }

        if ($elementRecord === null) {
            $elementRecord = new StructureElement;
            $elementRecord->structureId = $structureId;
            $elementRecord->elementId = $element->id;

            $mode = Mode::Insert;
        }

        [$beforeEvent, $afterEvent] = match ($mode) {
            Mode::Insert => [InsertingElement::class, ElementInserted::class],
            Mode::Update => [MovingElement::class, ElementMoved::class],
        };

        $targetElementId = $targetElementRecord->isRoot() ? null : $targetElementRecord->elementId;

        // Fire a 'beforeInsertElement' or 'beforeMoveElement' event
        if (Event::hasListeners($beforeEvent)) {
            Event::dispatch($event = new $beforeEvent(
                element: $element,
                structureId: $structureId,
                targetElementId: $targetElementId,
                action: $action,
            ));

            if (! $event->isValid) {
                Cache::lock($lockName)->release();

                return false;
            }
        }

        // Tell the element about it
        if (! $element->beforeMoveInStructure($structureId)) {
            Cache::lock($lockName)->release();

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
            if (! $elementRecord->$method($targetElementRecord)) {
                DB::rollBack();
                Cache::lock($lockName)->release();

                return false;
            }

            Cache::lock($lockName)->release();

            // Update the element with the latest values.
            // todo: we should be able to pull these from $elementRecord - https://github.com/creocoder/yii2-nested-sets/issues/114
            $values = (array) DB::table(Table::STRUCTUREELEMENTS)
                ->select(['root', 'lft', 'rgt', 'level'])
                ->where('structureId', $structureId)
                ->where('elementId', $element->id)
                ->first();

            $element->root = $values['root'];
            $element->lft = $values['lft'];
            $element->rgt = $values['rgt'];
            $element->level = $values['level'];

            // Tell the element about it
            $element->afterMoveInStructure($structureId);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Cache::lock($lockName)->release();
            throw $e;
        }

        // Invalidate all caches for the element type
        // (see https://github.com/craftcms/cms/issues/14846)
        \Craft::$app->getElements()->invalidateCachesForElementType($element::class);

        if (Event::hasListeners($afterEvent)) {
            Event::dispatch(new $afterEvent(
                element: $element,
                structureId: $structureId,
                targetElementId: $targetElementId,
                action: $action,
            ));
        }

        return true;
    }
}
