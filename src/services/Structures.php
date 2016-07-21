<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\errors\StructureNotFoundException;
use craft\app\events\MoveElementEvent;
use craft\app\models\Structure;
use craft\app\records\Structure as StructureRecord;
use craft\app\records\StructureElement as StructureElementRecord;
use yii\base\Component;

/**
 * Class Structures service.
 *
 * An instance of the Structures service is globally accessible in Craft via [[Application::structures `Craft::$app->getStructures()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Structures extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event MoveElementEvent The event that is triggered before an element is moved.
     *
     * You may set [[MoveElementEvent::isValid]] to `false` to prevent the element from getting moved.
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
     * @param integer $structureId
     *
     * @return Structure|null
     */
    public function getStructureById($structureId)
    {
        $structureRecord = StructureRecord::findOne($structureId);

        if ($structureRecord) {
            return Structure::create($structureRecord);
        }

        return null;
    }

    /**
     * Saves a structure
     *
     * @param Structure $structure
     *
     * @return boolean Whether the structure was saved successfully
     * @throws StructureNotFoundException if $structure->id is invalid
     */
    public function saveStructure(Structure $structure)
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
     * @param integer $structureId
     *
     * @return boolean
     */
    public function deleteStructureById($structureId)
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
     * @param integer          $structureId
     * @param ElementInterface $element
     *
     * @return integer
     */
    public function getElementLevelDelta($structureId, ElementInterface $element)
    {
        $elementRecord = $this->_getElementRecord($structureId, $element);
        $descendants = $elementRecord->descendants();
        $criteria = $descendants->getDbCriteria();
        $criteria->order = 'level desc';
        $deepestDescendant = $descendants->find();

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
     * @param integer          $structureId
     * @param ElementInterface $element
     * @param ElementInterface $parentElement
     * @param string           $mode Whether this is an "insert", "update", or "auto".
     *
     * @return boolean
     */
    public function prepend($structureId, ElementInterface $element, ElementInterface $parentElement, $mode = 'auto')
    {
        $parentElementRecord = $this->_getElementRecord($structureId, $parentElement);

        return $this->_doIt($structureId, $element, $parentElementRecord, 'prependTo', $mode);
    }

    /**
     * Appends an element to another within a given structure.
     *
     * @param integer          $structureId
     * @param ElementInterface $element
     * @param ElementInterface $parentElement
     * @param string           $mode Whether this is an "insert", "update", or "auto".
     *
     * @return boolean
     */
    public function append($structureId, ElementInterface $element, ElementInterface $parentElement, $mode = 'auto')
    {
        $parentElementRecord = $this->_getElementRecord($structureId, $parentElement);

        return $this->_doIt($structureId, $element, $parentElementRecord, 'appendTo', $mode);
    }

    /**
     * Prepends an element to the root of a given structure.
     *
     * @param integer          $structureId
     * @param ElementInterface $element
     * @param string           $mode Whether this is an "insert", "update", or "auto".
     *
     * @return boolean
     */
    public function prependToRoot($structureId, ElementInterface $element, $mode = 'auto')
    {
        $parentElementRecord = $this->_getRootElementRecord($structureId);

        return $this->_doIt($structureId, $element, $parentElementRecord, 'prependTo', $mode);
    }

    /**
     * Appends an element to the root of a given structure.
     *
     * @param integer          $structureId
     * @param ElementInterface $element
     * @param string           $mode Whether this is an "insert", "update", or "auto".
     *
     * @return boolean
     */
    public function appendToRoot($structureId, ElementInterface $element, $mode = 'auto')
    {
        $parentElementRecord = $this->_getRootElementRecord($structureId);

        return $this->_doIt($structureId, $element, $parentElementRecord, 'appendTo', $mode);
    }

    /**
     * Moves an element before another within a given structure.
     *
     * @param integer          $structureId
     * @param ElementInterface $element
     * @param ElementInterface $nextElement
     * @param string           $mode Whether this is an "insert", "update", or "auto".
     *
     * @return boolean
     */
    public function moveBefore($structureId, ElementInterface $element, ElementInterface $nextElement, $mode = 'auto')
    {
        $nextElementRecord = $this->_getElementRecord($structureId, $nextElement);

        return $this->_doIt($structureId, $element, $nextElementRecord, 'insertBefore', $mode);
    }

    /**
     * Moves an element after another within a given structure.
     *
     * @param integer          $structureId
     * @param ElementInterface $element
     * @param ElementInterface $prevElement
     * @param string           $mode Whether this is an "insert", "update", or "auto".
     *
     * @return boolean
     */
    public function moveAfter($structureId, ElementInterface $element, ElementInterface $prevElement, $mode = 'auto')
    {
        $prevElementRecord = $this->_getElementRecord($structureId, $prevElement);

        return $this->_doIt($structureId, $element, $prevElementRecord, 'insertAfter', $mode);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a structure element record from given structure and element IDs.
     *
     * @param integer          $structureId
     * @param ElementInterface $element
     *
     * @return StructureElementRecord|null
     */
    private function _getElementRecord($structureId, ElementInterface $element)
    {
        /** @var Element $element */
        $elementId = $element->id;

        if ($elementId) {
            return StructureElementRecord::findOne([
                'structureId' => $structureId,
                'elementId' => $elementId
            ]);
        }

        return null;
    }

    /**
     * Returns the root node for a given structure ID, or creates one if it doesn't exist.
     *
     * @param integer $structureId
     *
     * @return StructureElementRecord
     */
    private function _getRootElementRecord($structureId)
    {
        if (!isset($this->_rootElementRecordsByStructureId[$structureId])) {
            $elementRecord = StructureElementRecord::find()
                ->where(['structureId' => $structureId])
                ->roots()
                ->one();

            if (!$elementRecord) {
                // Create it
                $elementRecord = new StructureElementRecord();
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
     * @param  int                    $structureId
     * @param  ElementInterface       $element
     * @param  StructureElementRecord $targetElementRecord
     * @param  string                 $action
     * @param  string                 $mode
     *
     * @return boolean Whether it was done
     * @throws \Exception if reasons
     */
    private function _doIt($structureId, ElementInterface $element, StructureElementRecord $targetElementRecord, $action, $mode)
    {
        /** @var Element $element */
        // Figure out what we're doing
        if ($mode != 'insert') {
            // See if there's an existing structure element record
            $elementRecord = $this->_getElementRecord($structureId, $element);

            if ($elementRecord) {
                $mode = 'update';
            }
        }

        if (empty($elementRecord)) {
            $elementRecord = new StructureElementRecord();
            $elementRecord->structureId = $structureId;
            $elementRecord->elementId = $element->id;

            $mode = 'insert';
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if ($mode == 'update') {
                // Fire a 'beforeMoveElement' event
                $event = new MoveElementEvent([
                    'structureId' => $structureId,
                    'element' => $element,
                ]);

                $this->trigger(static::EVENT_BEFORE_MOVE_ELEMENT, $event);
            }

            // Was there was no onBeforeMoveElement event, or is the event giving us the go-ahead?
            if (!isset($event) || $event->isValid) {
                // Really do it
                $success = $elementRecord->$action($targetElementRecord);

                // If it didn't work, rollback the transaction in case something changed in onBeforeMoveElement
                if (!$success) {
                    $transaction->rollBack();

                    return false;
                }

                $element->root = $elementRecord->root;
                $element->lft = $elementRecord->lft;
                $element->rgt = $elementRecord->rgt;
                $element->level = $elementRecord->level;

                // Tell the element type about it
                $element::onAfterMoveElementInStructure($element, $structureId);
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we moved the element, in case something changed
            // in onBeforeMoveElement
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success && $mode == 'update') {
            // Fire an 'afterMoveElement' event
            $this->trigger(static::EVENT_AFTER_MOVE_ELEMENT,
                new MoveElementEvent([
                    'structureId' => $structureId,
                    'element' => $element,
                ]));
        }

        return $success;
    }
}
