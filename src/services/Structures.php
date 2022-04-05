<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\errors\StructureNotFoundException;
use craft\events\MoveElementEvent;
use craft\models\Structure;
use craft\records\Structure as StructureRecord;
use craft\records\StructureElement;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

/**
 * Structures service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getStructures()|`Craft::$app->structures`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Structures extends Component
{
    /**
     * @event MoveElementEvent The event that is triggered before an element is moved.
     */
    public const EVENT_BEFORE_MOVE_ELEMENT = 'beforeMoveElement';

    /**
     * @event MoveElementEvent The event that is triggered after an element is moved.
     */
    public const EVENT_AFTER_MOVE_ELEMENT = 'afterMoveElement';

    /**
     * @since 3.4.21
     */
    public const MODE_INSERT = 'insert';
    /**
     * @since 3.4.21
     */
    public const MODE_UPDATE = 'update';
    /**
     * @since 3.4.21
     */
    public const MODE_AUTO = 'auto';

    /**
     * @var int The timeout to pass to [[\yii\mutex\Mutex::acquire()]] when acquiring a lock on the structure.
     * @since 3.0.19
     */
    public int $mutexTimeout = 3;

    /**
     * @var StructureElement[]
     */
    private array $_rootElementRecordsByStructureId = [];

    // Structure CRUD
    // -------------------------------------------------------------------------

    /**
     * Returns a structure by its ID.
     *
     * @param int $structureId
     * @param bool $withTrashed
     * @return Structure|null
     */
    public function getStructureById(int $structureId, bool $withTrashed = false): ?Structure
    {
        $query = (new Query())
            ->select([
                'id',
                'maxLevels',
                'uid',
            ])
            ->from([Table::STRUCTURES])
            ->where(['id' => $structureId]);

        if (!$withTrashed) {
            $query->andWhere(['dateDeleted' => null]);
        }

        $result = $query->one();
        return $result ? new Structure($result) : null;
    }

    /**
     * Returns a structure by its UID.
     *
     * @param string $structureUid
     * @param bool $withTrashed
     * @return Structure|null
     */
    public function getStructureByUid(string $structureUid, bool $withTrashed = false): ?Structure
    {
        $query = (new Query())
            ->select([
                'id',
                'maxLevels',
                'uid',
            ])
            ->from([Table::STRUCTURES])
            ->where(['uid' => $structureUid]);

        if (!$withTrashed) {
            $query->andWhere(['dateDeleted' => null]);
        }

        $result = $query->one();
        return $result ? new Structure($result) : null;
    }

    /**
     * Patches an array of entries, filling in any gaps in the tree.
     *
     * @param ElementInterface[] $elements
     * @since 3.6.0
     */
    public function fillGapsInElements(array &$elements): void
    {
        /** @var ElementInterface|null $prevElement */
        $prevElement = null;
        $patchedElements = [];

        foreach ($elements as $i => $element) {
            // Did we just skip any elements?
            if (
                $element->level != 1 &&
                (
                    $i == 0 ||
                    (!$element->isSiblingOf($prevElement) && !$element->isChildOf($prevElement))
                )
            ) {
                // Merge in any missing ancestors
                $ancestorQuery = $element->getAncestors()
                    ->status(null);

                if ($prevElement) {
                    $ancestorQuery->andWhere(['>', 'structureelements.lft', $prevElement->lft]);
                }

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
     * @param ElementInterface[] $elements
     * @param int $branchLimit
     * @since 3.6.0
     */
    public function applyBranchLimitToElements(array &$elements, int $branchLimit): void
    {
        $branchCount = 0;
        $prevElement = null;

        foreach ($elements as $i => $element) {
            // Is this a new branch?
            if ($prevElement === null || !$element->isDescendantOf($prevElement)) {
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
     * @param Structure $structure
     * @return bool Whether the structure was saved successfully
     * @throws StructureNotFoundException if $structure->id is invalid
     */
    public function saveStructure(Structure $structure): bool
    {
        if ($structure->id) {
            /** @var StructureRecord|null $structureRecord */
            $structureRecord = StructureRecord::findWithTrashed()
                ->andWhere(['id' => $structure->id])
                ->one();

            if (!$structureRecord) {
                throw new StructureNotFoundException("No structure exists with the ID '$structure->id'");
            }
        } else {
            $structureRecord = new StructureRecord();
        }

        $structureRecord->maxLevels = $structure->maxLevels;
        $structureRecord->uid = $structure->uid;

        if ($structureRecord->dateDeleted) {
            $success = $structureRecord->restore();
        } else {
            $success = $structureRecord->save();
        }

        if ($success) {
            $structure->id = $structureRecord->id;
        } else {
            $structure->addErrors($structureRecord->getErrors());
        }

        return $success;
    }

    /**
     * Deletes a structure by its ID.
     *
     * @param int $structureId
     * @return bool
     */
    public function deleteStructureById(int $structureId): bool
    {
        if (!$structureId) {
            return false;
        }

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->softDelete(Table::STRUCTURES, [
                'id' => $structureId,
            ])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Returns the descendant level delta for a given element.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @return int
     */
    public function getElementLevelDelta(int $structureId, ElementInterface $element): int
    {
        $elementRecord = $this->_getElementRecord($structureId, $element);
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
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param int|ElementInterface $parentElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function prepend(int $structureId, ElementInterface $element, ElementInterface|int $parentElement, string $mode = self::MODE_AUTO): bool
    {
        $parentElementRecord = $this->_getElementRecord($structureId, $parentElement);

        if ($parentElementRecord === null) {
            throw new Exception('There was a problem getting the parent element.');
        }

        return $this->_doIt($structureId, $element, $parentElementRecord, 'prependTo', $mode);
    }

    /**
     * Appends an element to another within a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param int|ElementInterface $parentElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function append(int $structureId, ElementInterface $element, ElementInterface|int $parentElement, string $mode = self::MODE_AUTO): bool
    {
        $parentElementRecord = $this->_getElementRecord($structureId, $parentElement);

        if ($parentElementRecord === null) {
            throw new Exception('There was a problem getting the parent element.');
        }

        return $this->_doIt($structureId, $element, $parentElementRecord, 'appendTo', $mode);
    }

    /**
     * Prepends an element to the root of a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function prependToRoot(int $structureId, ElementInterface $element, string $mode = self::MODE_AUTO): bool
    {
        $parentElementRecord = $this->_getRootElementRecord($structureId);
        return $this->_doIt($structureId, $element, $parentElementRecord, 'prependTo', $mode);
    }

    /**
     * Appends an element to the root of a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function appendToRoot(int $structureId, ElementInterface $element, string $mode = self::MODE_AUTO): bool
    {
        $parentElementRecord = $this->_getRootElementRecord($structureId);
        return $this->_doIt($structureId, $element, $parentElementRecord, 'appendTo', $mode);
    }

    /**
     * Moves an element before another within a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param int|ElementInterface $nextElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function moveBefore(int $structureId, ElementInterface $element, ElementInterface|int $nextElement, string $mode = self::MODE_AUTO): bool
    {
        $nextElementRecord = $this->_getElementRecord($structureId, $nextElement);

        if ($nextElementRecord === null) {
            throw new Exception('There was a problem getting the next element.');
        }

        return $this->_doIt($structureId, $element, $nextElementRecord, 'insertBefore', $mode);
    }

    /**
     * Moves an element after another within a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param int|ElementInterface $prevElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function moveAfter(int $structureId, ElementInterface $element, ElementInterface|int $prevElement, string $mode = self::MODE_AUTO): bool
    {
        $prevElementRecord = $this->_getElementRecord($structureId, $prevElement);

        if ($prevElementRecord === null) {
            throw new Exception('There was a problem getting the previous element.');
        }

        return $this->_doIt($structureId, $element, $prevElementRecord, 'insertAfter', $mode);
    }

    /**
     * Removes an element from a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @return bool
     * @throws Exception
     * @since 3.7.19
     */
    public function remove(int $structureId, ElementInterface $element): bool
    {
        $elementRecord = $this->_getElementRecord($structureId, $element);

        if ($elementRecord && !$elementRecord->delete()) {
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
     *
     * @param int $structureId
     * @param int|ElementInterface $element
     * @return StructureElement|null
     */
    private function _getElementRecord(int $structureId, ElementInterface|int $element): ?StructureElement
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
     *
     * @param int $structureId
     * @return StructureElement
     */
    private function _getRootElementRecord(int $structureId): StructureElement
    {
        if (!isset($this->_rootElementRecordsByStructureId[$structureId])) {
            /** @var StructureElement|null $elementRecord */
            $elementRecord = StructureElement::find()
                ->where(['structureId' => $structureId])
                ->roots()
                ->one();

            if (!$elementRecord) {
                // Create it
                $elementRecord = new StructureElement();
                $elementRecord->structureId = $structureId;
                $elementRecord->makeRoot();
            }

            $this->_rootElementRecordsByStructureId[$structureId] = $elementRecord;
        }

        return $this->_rootElementRecordsByStructureId[$structureId];
    }

    /**
     * Updates a ElementInterface with the new structure attributes from a StructureElement record.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param StructureElement $targetElementRecord
     * @param string $action
     * @param string $mode
     * @return bool Whether it was done
     * @throws Throwable if reasons
     */
    private function _doIt(int $structureId, ElementInterface $element, StructureElement $targetElementRecord, string $action, string $mode): bool
    {
        // Get a lock or bust
        $lockName = 'structure:' . $structureId;
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lockName, $this->mutexTimeout)) {
            throw new Exception('Unable to acquire a lock for the structure ' . $structureId);
        }

        $elementRecord = null;

        /** @var Element $element */
        // Figure out what we're doing
        if ($mode !== self::MODE_INSERT) {
            // See if there's an existing structure element record
            $elementRecord = $this->_getElementRecord($structureId, $element);

            if ($elementRecord !== null) {
                $mode = self::MODE_UPDATE;
            }
        }

        if ($elementRecord === null) {
            $elementRecord = new StructureElement();
            $elementRecord->structureId = $structureId;
            $elementRecord->elementId = $element->id;

            $mode = self::MODE_INSERT;
        }

        if ($mode === self::MODE_UPDATE && $this->hasEventHandlers(self::EVENT_BEFORE_MOVE_ELEMENT)) {
            // Fire a 'beforeMoveElement' event
            $this->trigger(self::EVENT_BEFORE_MOVE_ELEMENT, new MoveElementEvent([
                'structureId' => $structureId,
                'element' => $element,
            ]));
        }

        // Tell the element about it
        if (!$element->beforeMoveInStructure($structureId)) {
            $mutex->release($lockName);
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$elementRecord->$action($targetElementRecord)) {
                $transaction->rollBack();
                $mutex->release($lockName);
                return false;
            }

            $mutex->release($lockName);

            // Update the element with the latest values.
            // todo: we should be able to pull these from $elementRecord - https://github.com/creocoder/yii2-nested-sets/issues/114
            $values = (new Query())
                ->select(['root', 'lft', 'rgt', 'level'])
                ->from(Table::STRUCTUREELEMENTS)
                ->where([
                    'structureId' => $structureId,
                    'elementId' => $element->id,
                ])
                ->one();

            $element->root = $values['root'];
            $element->lft = $values['lft'];
            $element->rgt = $values['rgt'];
            $element->level = $values['level'];

            // Tell the element about it
            $element->afterMoveInStructure($structureId);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            $mutex->release($lockName);
            throw $e;
        }

        if ($mode === self::MODE_UPDATE && $this->hasEventHandlers(self::EVENT_AFTER_MOVE_ELEMENT)) {
            // Fire an 'afterMoveElement' event
            $this->trigger(self::EVENT_AFTER_MOVE_ELEMENT, new MoveElementEvent([
                'structureId' => $structureId,
                'element' => $element,
            ]));
        }

        return true;
    }
}
