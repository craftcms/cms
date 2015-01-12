<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\errors\Exception;
use craft\app\events\MoveElementEvent;
use craft\app\models\BaseElementModel;
use craft\app\models\Structure as StructureModel;
use craft\app\records\Structure as StructureRecord;
use craft\app\records\StructureElement as StructureElementRecord;
use yii\base\Component;

/**
 * Class Structures service.
 *
 * An instance of the Structures service is globally accessible in Craft via [[Application::structures `Craft::$app->structures`]].
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
     *
     * You may set [[MoveElementEvent::performAction]] to `false` to prevent the element from getting moved.
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
	 *
	 * @return StructureModel|null
	 */
	public function getStructureById($structureId)
	{
		$structureRecord = StructureRecord::model()->findById($structureId);

		if ($structureRecord)
		{
			return StructureModel::populateModel($structureRecord);
		}
	}

	/**
	 * Saves a structure
	 *
	 * @param StructureModel $structure
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function saveStructure(StructureModel $structure)
	{
		if ($structure->id)
		{
			$structureRecord = StructureRecord::model()->findById($structure->id);

			if (!$structureRecord)
			{
				throw new Exception(Craft::t('No structure exists with the ID “{id}”.', ['id' => $structure->id]));
			}
		}
		else
		{
			$structureRecord = new StructureRecord();
		}

		$structureRecord->maxLevels = $structure->maxLevels;

		$success = $structureRecord->save();

		if ($success)
		{
			$structure->id = $structureRecord->id;
		}
		else
		{
			$structure->addErrors($structureRecord->getErrors());
		}

		return $success;
	}

	/**
	 * Deletes a structure by its ID.
	 *
	 * @param int $structureId
	 *
	 * @return bool
	 */
	public function deleteStructureById($structureId)
	{
		if (!$structureId)
		{
			return false;
		}

		$affectedRows = Craft::$app->db->createCommand()->delete('structures', [
			'id' => $structureId
		]);

		return (bool) $affectedRows;
	}

	/**
	 * Returns the descendant level delta for a given element.
	 *
	 * @param int              $structureId
	 * @param BaseElementModel $element
	 *
	 * @return int
	 */
	public function getElementLevelDelta($structureId, BaseElementModel $element)
	{
		$elementRecord = $this->_getElementRecord($structureId, $element);
		$descendants = $elementRecord->descendants();
		$criteria = $descendants->getDbCriteria();
		$criteria->order = 'level desc';
		$deepestDescendant = $descendants->find();

		if ($deepestDescendant)
		{
			return $deepestDescendant->level - $elementRecord->level;
		}
		else
		{
			return 0;
		}
	}

	// Moving elements around
	// -------------------------------------------------------------------------

	/**
	 * Prepends an element to another within a given structure.
	 *
	 * @param int              $structureId
	 * @param BaseElementModel $element
	 * @param BaseElementModel $parentElement
	 * @param string           $mode          Whether this is an "insert", "update", or "auto".
	 *
	 * @return bool
	 */
	public function prepend($structureId, BaseElementModel $element, BaseElementModel $parentElement, $mode = 'auto')
	{
		$parentElementRecord = $this->_getElementRecord($structureId, $parentElement);

		return $this->_doIt($structureId, $element, $parentElementRecord, 'prependTo', 'moveAsFirst', $mode);
	}

	/**
	 * Appends an element to another within a given structure.
	 *
	 * @param int              $structureId
	 * @param BaseElementModel $element
	 * @param BaseElementModel $parentElement
	 * @param string           $mode          Whether this is an "insert", "update", or "auto".
	 *
	 * @return bool
	 */
	public function append($structureId, BaseElementModel $element, BaseElementModel $parentElement, $mode = 'auto')
	{
		$parentElementRecord = $this->_getElementRecord($structureId, $parentElement);

		return $this->_doIt($structureId, $element, $parentElementRecord, 'appendTo', 'moveAsLast', $mode);
	}

	/**
	 * Prepends an element to the root of a given structure.
	 *
	 * @param int              $structureId
	 * @param BaseElementModel $element
	 * @param string           $mode        Whether this is an "insert", "update", or "auto".
	 *
	 * @return bool
	 */
	public function prependToRoot($structureId, BaseElementModel $element, $mode = 'auto')
	{
		$parentElementRecord = $this->_getRootElementRecord($structureId);

		return $this->_doIt($structureId, $element, $parentElementRecord, 'prependTo', 'moveAsFirst', $mode);
	}

	/**
	 * Appends an element to the root of a given structure.
	 *
	 * @param int              $structureId
	 * @param BaseElementModel $element
	 * @param string           $mode        Whether this is an "insert", "update", or "auto".
	 *
	 * @return bool
	 */
	public function appendToRoot($structureId, BaseElementModel $element, $mode = 'auto')
	{
		$parentElementRecord = $this->_getRootElementRecord($structureId);

		return $this->_doIt($structureId, $element, $parentElementRecord, 'appendTo', 'moveAsLast', $mode);
	}

	/**
	 * Moves an element before another within a given structure.
	 *
	 * @param int              $structureId
	 * @param BaseElementModel $element
	 * @param BaseElementModel $nextElement
	 * @param string           $mode        Whether this is an "insert", "update", or "auto".
	 *
	 * @return bool
	 */
	public function moveBefore($structureId, BaseElementModel $element, BaseElementModel $nextElement, $mode = 'auto')
	{
		$nextElementRecord = $this->_getElementRecord($structureId, $nextElement);

		return $this->_doIt($structureId, $element, $nextElementRecord, 'insertBefore', 'moveBefore', $mode);
	}

	/**
	 * Moves an element after another within a given structure.
	 *
	 * @param int              $structureId
	 * @param BaseElementModel $element
	 * @param BaseElementModel $prevElement
	 * @param string           $mode        Whether this is an "insert", "update", or "auto".
	 *
	 * @return bool
	 */
	public function moveAfter($structureId, BaseElementModel $element, BaseElementModel $prevElement, $mode = 'auto')
	{
		$prevElementRecord = $this->_getElementRecord($structureId, $prevElement);

		return $this->_doIt($structureId, $element, $prevElementRecord, 'insertAfter', 'moveAfter', $mode);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a structure element record from given structure and element IDs.
	 *
	 * @param int              $structureId
	 * @param BaseElementModel $element
	 *
	 * @return StructureElementRecord|null
	 */
	private function _getElementRecord($structureId, BaseElementModel $element)
	{
		$elementId = $element->id;

		if ($elementId)
		{
			return StructureElementRecord::model()->findByAttributes([
				'structureId' => $structureId,
				'elementId'   => $elementId
			]);
		}
	}

	/**
	 * Returns the root node for a given structure ID, or creates one if it doesn't exist.
	 *
	 * @param int $structureId
	 *
	 * @return StructureElementRecord
	 */
	private function _getRootElementRecord($structureId)
	{
		if (!isset($this->_rootElementRecordsByStructureId[$structureId]))
		{
			$elementRecord = StructureElementRecord::model()->roots()->findByAttributes([
				'structureId' => $structureId
			]);

			if (!$elementRecord)
			{
				// Create it
				$elementRecord = new StructureElementRecord();
				$elementRecord->structureId = $structureId;
				$elementRecord->saveNode();
			}

			$this->_rootElementRecordsByStructureId[$structureId] = $elementRecord;
		}

		return $this->_rootElementRecordsByStructureId[$structureId];
	}

	/**
	 * Updates a BaseElementModel with the new structure attributes from a StructureElement record.
	 *
	 * @param  int                    $structureId
	 * @param  BaseElementModel       $element
	 * @param  StructureElementRecord $targetElementRecord
	 * @param  string                 $insertAction
	 * @param  string                 $updateAction
	 * @param  string                 $mode
	 *
	 * @throws \Exception
	 * @return bool
	 */
	private function _doIt($structureId, BaseElementModel $element, StructureElementRecord $targetElementRecord, $insertAction, $updateAction, $mode)
	{
		// Figure out what we're doing
		if ($mode != 'insert')
		{
			// See if there's an existing structure element record
			$elementRecord = $this->_getElementRecord($structureId, $element);

			if ($elementRecord)
			{
				$mode = 'update';
				$action = $updateAction;
			}
		}

		if (empty($elementRecord))
		{
			$elementRecord = new StructureElementRecord();
			$elementRecord->structureId = $structureId;
			$elementRecord->elementId   = $element->id;

			$mode = 'insert';
			$action = $insertAction;
		}

		$transaction = Craft::$app->db->getCurrentTransaction() === null ? Craft::$app->db->beginTransaction() : null;
		try
		{
			if ($mode == 'update')
			{
				// Fire a 'beforeMoveElement' event
				$event = new MoveElementEvent([
					'structureId'  => $structureId,
					'element'      => $element,
				]);

				$this->trigger(static::EVENT_BEFORE_MOVE_ELEMENT, $event);
			}

			// Was there was no onBeforeMoveElement event, or is the event giving us the go-ahead?
			if (!isset($event) || $event->performAction)
			{
				// Really do it
				$success = $elementRecord->$action($targetElementRecord);

				// If it didn't work, rollback the transaction in case something changed in onBeforeMoveElement
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					return false;
				}

				$element->root  = $elementRecord->root;
				$element->lft   = $elementRecord->lft;
				$element->rgt   = $elementRecord->rgt;
				$element->level = $elementRecord->level;

				// Tell the element type about it
				$elementType = Craft::$app->elements->getElementType($element->getElementType());
				$elementType->onAfterMoveElementInStructure($element, $structureId);
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we moved the element, in case something changed
			// in onBeforeMoveElement
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success && $mode == 'update')
		{
			// Fire an 'afterMoveElement' event
			$this->trigger(static::EVENT_AFTER_MOVE_ELEMENT, new MoveElementEvent([
				'structureId'  => $structureId,
				'element'      => $element,
			]));
		}

		return $success;
	}
}
