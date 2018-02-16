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
use craft\errors\StructureNotFoundException;
use craft\events\MoveElementEvent;
use craft\models\Structure;
use craft\records\Structure as StructureRecord;
use craft\records\StructureElement;
use yii\base\Component;
use yii\base\Exception;

/**
 * Structures service.
 * An instance of the Structures service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getStructures()|<code>Craft::$app->structures</code>]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Structures extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event MoveElementEvent The event that is triggered before an element is moved.
     */
    const EVENT_BEFORE_MOVE_ELEMENT = 'beforeMoveElement';

    /**
     * @event MoveElementEvent The event that is triggered after an element is moved.
     */
    const EVENT_AFTER_MOVE_ELEMENT = 'afterMoveElement';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_rootElementRecordsByStructureId;

    // Public Methods
    // =========================================================================

    // Structure CRUD
    // -------------------------------------------------------------------------

    /**
     * Returns a structure by its ID.
     *
     * @param int $structureId
     * @return Structure|null
     */
    public function getStructureById(int $structureId)
    {
        $result = (new Query())
            ->select([
                'id',
                'maxLevels',
            ])
            ->from(['{{%structures}}'])
            ->where(['id' => $structureId])
            ->one();

        return $result ? new Structure($result) : null;
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
            $structureRecord = StructureRecord::findOne($structure->id);

            if (!$structureRecord) {
                throw new StructureNotFoundException("No structure exists with the ID '{$structure->id}'");
            }
        } else {
            $structureRecord = new StructureRecord();
        }

        $structureRecord->maxLevels = $structure->maxLevels;

        $success = $structureRecord->save();

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
            ->delete(
                '{{%structures}}',
                [
                    'id' => $structureId
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
        /** @var StructureElement $deepestDescendant */
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
     * @param ElementInterface $parentElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function prepend(int $structureId, ElementInterface $element, ElementInterface $parentElement, string $mode = 'auto'): bool
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
     * @param ElementInterface $parentElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function append(int $structureId, ElementInterface $element, ElementInterface $parentElement, string $mode = 'auto'): bool
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
    public function prependToRoot(int $structureId, ElementInterface $element, string $mode = 'auto'): bool
    {
        $parentElementRecord = $this->_getRootElementRecord($structureId);

        if ($parentElementRecord === null) {
            throw new Exception('There was a problem getting the parent element.');
        }

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
    public function appendToRoot(int $structureId, ElementInterface $element, string $mode = 'auto'): bool
    {
        $parentElementRecord = $this->_getRootElementRecord($structureId);

        if ($parentElementRecord === null) {
            throw new Exception('There was a problem getting the parent element.');
        }

        return $this->_doIt($structureId, $element, $parentElementRecord, 'appendTo', $mode);
    }

    /**
     * Moves an element before another within a given structure.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @param ElementInterface $nextElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function moveBefore(int $structureId, ElementInterface $element, ElementInterface $nextElement, string $mode = 'auto'): bool
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
     * @param ElementInterface $prevElement
     * @param string $mode Whether this is an "insert", "update", or "auto".
     * @return bool
     * @throws Exception
     */
    public function moveAfter(int $structureId, ElementInterface $element, ElementInterface $prevElement, string $mode = 'auto'): bool
    {
        $prevElementRecord = $this->_getElementRecord($structureId, $prevElement);

        if ($prevElementRecord === null) {
            throw new Exception('There was a problem getting the previous element.');
        }

        return $this->_doIt($structureId, $element, $prevElementRecord, 'insertAfter', $mode);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a structure element record from given structure and element IDs.
     *
     * @param int $structureId
     * @param ElementInterface $element
     * @return StructureElement|null
     */
    private function _getElementRecord(int $structureId, ElementInterface $element)
    {
        /** @var Element $element */
        $elementId = $element->id;

        if ($elementId) {
            return StructureElement::findOne([
                'structureId' => $structureId,
                'elementId' => $elementId
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
     * @throws \Throwable if reasons
     */
    private function _doIt($structureId, ElementInterface $element, StructureElement $targetElementRecord, $action, $mode): bool
    {
        $elementRecord = null;

        /** @var Element $element */
        // Figure out what we're doing
        if ($mode !== 'insert') {
            // See if there's an existing structure element record
            $elementRecord = $this->_getElementRecord($structureId, $element);

            if ($elementRecord !== null) {
                $mode = 'update';
            }
        }

        if ($elementRecord === null) {
            $elementRecord = new StructureElement();
            $elementRecord->structureId = $structureId;
            $elementRecord->elementId = $element->id;

            $mode = 'insert';
        }

        if ($mode === 'update' && $this->hasEventHandlers(self::EVENT_BEFORE_MOVE_ELEMENT)) {
            // Fire a 'beforeMoveElement' event
            $this->trigger(self::EVENT_BEFORE_MOVE_ELEMENT, new MoveElementEvent([
                'structureId' => $structureId,
                'element' => $element,
            ]));
        }

        // Tell the element about it
        if (!$element->beforeMoveInStructure($structureId)) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$elementRecord->$action($targetElementRecord)) {
                $transaction->rollBack();

                return false;
            }

            // Update the element with the latest values.
            // todo: we should be able to pull these from $elementRecord - https://github.com/creocoder/yii2-nested-sets/issues/114
            $values = (new Query())
                ->select(['root', 'lft', 'rgt', 'level'])
                ->from('{{%structureelements}}')
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
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($mode === 'update' && $this->hasEventHandlers(self::EVENT_AFTER_MOVE_ELEMENT)) {
            // Fire an 'afterMoveElement' event
            $this->trigger(self::EVENT_AFTER_MOVE_ELEMENT, new MoveElementEvent([
                'structureId' => $structureId,
                'element' => $element,
            ]));
        }

        return true;
    }
}
