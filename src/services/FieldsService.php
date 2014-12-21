<?php
namespace Craft;

/**
 * Class FieldsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class FieldsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $oldFieldColumnPrefix = 'field_';

	/**
	 * @var
	 */
	private $_groupsById;

	/**
	 * @var bool
	 */
	private $_fetchedAllGroups = false;

	/**
	 * @var
	 */
	private $_fieldRecordsById;

	/**
	 * @var
	 */
	private $_fieldsById;

	/**
	 * @var
	 */
	private $_allFieldsInContext;

	/**
	 * @var
	 */
	private $_fieldsByContextAndHandle;

	/**
	 * @var
	 */
	private $_fieldsWithContent;

	/**
	 * @var
	 */
	private $_layoutsById;

	/**
	 * @var
	 */
	private $_layoutsByType;

	// Public Methods
	// =========================================================================

	// Groups
	// -------------------------------------------------------------------------

	/**
	 * Returns all field groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		if (!$this->_fetchedAllGroups)
		{
			$this->_groupsById = array();

			$results = $this->_createGroupQuery()->queryAll();

			foreach ($results as $result)
			{
				$group = new FieldGroupModel($result);
				$this->_groupsById[$group->id] = $group;
			}

			$this->_fetchedAllGroups = true;
		}

		if ($indexBy == 'id')
		{
			$groups = $this->_groupsById;
		}
		else if (!$indexBy)
		{
			$groups = array_values($this->_groupsById);
		}
		else
		{
			$groups = array();

			foreach ($this->_groupsById as $group)
			{
				$groups[$group->$indexBy] = $group;
			}
		}

		return $groups;
	}

	/**
	 * Returns a field group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @return FieldGroupModel|null
	 */
	public function getGroupById($groupId)
	{
		if (!isset($this->_groupsById) || !array_key_exists($groupId, $this->_groupsById))
		{
			$result = $this->_createGroupQuery()
				->where('id = :id', array(':id' => $groupId))
				->queryRow();

			if ($result)
			{
				$group = new FieldGroupModel($result);
			}
			else
			{
				$group = null;
			}

			$this->_groupsById[$groupId] = $group;
		}

		return $this->_groupsById[$groupId];
	}

	/**
	 * Saves a field group.
	 *
	 * @param FieldGroupModel $group
	 *
	 * @return bool
	 */
	public function saveGroup(FieldGroupModel $group)
	{
		$groupRecord = $this->_getGroupRecord($group);
		$groupRecord->name = $group->name;

		if ($groupRecord->validate())
		{
			$groupRecord->save(false);

			// Now that we have an ID, save it on the model & models
			if (!$group->id)
			{
				$group->id = $groupRecord->id;
			}

			return true;
		}
		else
		{
			$group->addErrors($groupRecord->getErrors());
			return false;
		}
	}

	/**
	 * Deletes a field group.
	 *
	 * @param int $groupId
	 *
	 * @return bool
	 */
	public function deleteGroupById($groupId)
	{
		$groupRecord = FieldGroupRecord::model()->with('fields')->findById($groupId);

		if (!$groupRecord)
		{
			return false;
		}

		// Manually delete the fields (rather than relying on cascade deletes) so we have a chance to delete the
		// content columns
		foreach ($groupRecord->fields as $fieldRecord)
		{
			$field = FieldModel::populateModel($fieldRecord);
			$this->deleteField($field);
		}

		$affectedRows = craft()->db->createCommand()->delete('fieldgroups', array('id' => $groupId));
		return (bool) $affectedRows;
	}

	// Fields
	// -------------------------------------------------------------------------

	/**
	 * Returns all fields.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllFields($indexBy = null)
	{
		$context = craft()->content->fieldContext;

		if (!isset($this->_allFieldsInContext[$context]))
		{
			$results = $this->_createFieldQuery()
				->where('context = :context', array(':context' => $context))
				->queryAll();

			$this->_allFieldsInContext[$context] = array();

			foreach ($results as $result)
			{
				$field = $this->_populateField($result);

				$this->_allFieldsInContext[$context][] = $field;
				$this->_fieldsById[$field->id] = $field;
				$this->_fieldsByContextAndHandle[$context][$field->handle] = $field;
			}
		}

		if (!$indexBy)
		{
			$fields = $this->_allFieldsInContext[$context];
		}
		else
		{
			$fields = array();

			foreach ($this->_allFieldsInContext[$context] as $field)
			{
				$fields[$field->$indexBy] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Returns all fields that have a column in the content table.
	 *
	 * @return array
	 */
	public function getFieldsWithContent()
	{
		$context = craft()->content->fieldContext;

		if (!isset($this->_fieldsWithContent[$context]))
		{
			$this->_fieldsWithContent[$context] = array();

			foreach ($this->getAllFields() as $field)
			{
				if ($field->hasContentColumn())
				{
					$this->_fieldsWithContent[$context][] = $field;
				}
			}
		}

		return $this->_fieldsWithContent[$context];
	}

	/**
	 * Returns a field by its ID.
	 *
	 * @param int $fieldId
	 *
	 * @return FieldModel|null
	 */
	public function getFieldById($fieldId)
	{
		if (!isset($this->_fieldsById) || !array_key_exists($fieldId, $this->_fieldsById))
		{
			$result = $this->_createFieldQuery()
				->where('id = :id', array(':id' => $fieldId))
				->queryRow();

			if ($result)
			{
				$field = $this->_populateField($result);

				$this->_fieldsById[$field->id] = $field;
				$this->_fieldsByContextAndHandle[$field->context][$field->handle] = $field;
			}
			else
			{
				return null;
			}
		}

		return $this->_fieldsById[$fieldId];
	}

	/**
	 * Returns a field by its handle.
	 *
	 * @param string $handle
	 *
	 * @return FieldModel|null
	 */
	public function getFieldByHandle($handle)
	{
		$context = craft()->content->fieldContext;

		if (!isset($this->_fieldsByContextAndHandle[$context]) || !array_key_exists($handle, $this->_fieldsByContextAndHandle[$context]))
		{
			$result = $this->_createFieldQuery()
				->where(array('and', 'handle = :handle', 'context = :context'), array(':handle' => $handle, ':context' => $context))
				->queryRow();

			if ($result)
			{
				$field = $this->_populateField($result);
				$this->_fieldsById[$field->id] = $field;
				$this->_fieldsByContextAndHandle[$context][$field->handle] = $field;
			}
			else
			{
				$this->_fieldsByContextAndHandle[$context][$handle] = null;
			}
		}

		return $this->_fieldsByContextAndHandle[$context][$handle];
	}

	/**
	 * Returns all the fields in a given group.
	 *
	 * @param int         $groupId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getFieldsByGroupId($groupId, $indexBy = null)
	{
		$results = $this->_createFieldQuery()
			->where('groupId = :groupId', array(':groupId' => $groupId))
			->queryAll();

		$fields = array();

		foreach ($results as $result)
		{
			$field = $this->_populateField($result);

			if ($indexBy)
			{
				$fields[$field->$indexBy] = $field;
			}
			else
			{
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Validates a field's settings.
	 *
	 * @param FieldModel $field
	 *
	 * @return bool
	 */
	public function validateField(FieldModel $field)
	{
		$fieldRecord = $this->_getFieldRecord($field);

		if (!$field->context)
		{
			$field->context = craft()->content->fieldContext;
		}

		$fieldRecord->groupId      = $field->groupId;
		$fieldRecord->name         = $field->name;
		$fieldRecord->handle       = $field->handle;
		$fieldRecord->context      = $field->context;
		$fieldRecord->instructions = $field->instructions;
		$fieldRecord->translatable = $field->translatable;
		$fieldRecord->type         = $field->type;

		// Get the field type
		$fieldType = $field->getFieldType();

		// Give the field type a chance to prep the settings from post
		$preppedSettings = $fieldType->prepSettings($field->settings);

		// Set the prepped settings on the FieldRecord and the field type
		$fieldRecord->settings = $preppedSettings;
		$fieldType->setSettings($preppedSettings);

		// Run validation
		$recordValidates = $fieldRecord->validate();
		$settingsValidate = $fieldType->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			return true;
		}
		else
		{
			$field->addErrors($fieldRecord->getErrors());
			$field->addSettingErrors($fieldType->getSettings()->getErrors());
			return false;
		}
	}

	/**
	 * Saves a field.
	 *
	 * @param FieldModel $field
	 * @param bool       $validate
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveField(FieldModel $field, $validate = true)
	{
		if (!$validate || $this->validateField($field))
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				$field->context = craft()->content->fieldContext;

				$fieldRecord = $this->_getFieldRecord($field);
				$isNewField = $fieldRecord->isNewRecord();

				$fieldRecord->groupId      = $field->groupId;
				$fieldRecord->name         = $field->name;
				$fieldRecord->handle       = $field->handle;
				$fieldRecord->context      = $field->context;
				$fieldRecord->instructions = $field->instructions;
				$fieldRecord->translatable = $field->translatable;
				$fieldRecord->type         = $field->type;

				// Get the field type
				$fieldType = $field->getFieldType();

				// Give the field type a chance to prep the settings from post
				$preppedSettings = $fieldType->prepSettings($field->settings);

				// Set the prepped settings on the FieldRecord, FieldModel, and the field type
				$fieldRecord->settings = $field->settings = $preppedSettings;
				$fieldType->setSettings($preppedSettings);

				if ($fieldRecord->settings instanceof BaseModel)
				{
					// Call getAttributes() without passing 'true' so the __model__ isn't saved
					$fieldRecord->settings = $fieldRecord->settings->getAttributes();
				}

				$fieldType->onBeforeSave();
				$fieldRecord->save(false);

				// Now that we have a field ID, save it on the model
				if ($isNewField)
				{
					$field->id = $fieldRecord->id;
				}

				// Create/alter the content table column
				$columnType = $fieldType->defineContentAttribute();

				$contentTable = craft()->content->contentTable;
				$oldColumnName = $this->oldFieldColumnPrefix.$fieldRecord->getOldHandle();
				$newColumnName = craft()->content->fieldColumnPrefix.$field->handle;

				if ($columnType)
				{
					$columnType = ModelHelper::normalizeAttributeConfig($columnType);

					// Make sure we're working with the latest data in the case of a renamed field.
					craft()->db->schema->refresh();

					if (craft()->db->columnExists($contentTable, $oldColumnName))
					{
						craft()->db->createCommand()->alterColumn($contentTable, $oldColumnName, $columnType, $newColumnName);
					}
					else if (craft()->db->columnExists($contentTable, $newColumnName))
					{
						craft()->db->createCommand()->alterColumn($contentTable, $newColumnName, $columnType);
					}
					else
					{
						craft()->db->createCommand()->addColumn($contentTable, $newColumnName, $columnType);
					}
				}
				else
				{
					// Did the old field have a column we need to remove?
					if (!$isNewField)
					{
						if ($fieldRecord->getOldHandle() && craft()->db->columnExists($contentTable, $oldColumnName))
						{
							craft()->db->createCommand()->dropColumn($contentTable, $oldColumnName);
						}
					}
				}

				if (!$isNewField)
				{
					// Save the old field handle on the model in case the field type needs to do something with it.
					$field->oldHandle = $fieldRecord->getOldHandle();

					unset($this->_fieldsByContextAndHandle[$field->context][$field->oldHandle]);
				}

				// Cache it
				$this->_fieldsById[$field->id] = $field;
				$this->_fieldsByContextAndHandle[$field->context][$field->handle] = $field;
				unset($this->_allFieldsInContext[$field->context]);
				unset($this->_fieldsWithContent[$field->context]);

				$fieldType->onAfterSave();

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

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a field by its ID.
	 *
	 * @param int $fieldId
	 *
	 * @return bool
	 */
	public function deleteFieldById($fieldId)
	{
		$fieldRecord = FieldRecord::model()->findById($fieldId);

		if (!$fieldRecord)
		{
			return false;
		}

		$field = FieldModel::populateModel($fieldRecord);
		return $this->deleteField($field);
	}

	/**
	 * Deletes a field.
	 *
	 * @param FieldModel $field
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteField(FieldModel $field)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			$fieldType = $field->getFieldType();

			if ($fieldType)
			{
				$field->getFieldType()->onBeforeDelete();
			}

			// De we need to delete the content column?
			$contentTable = craft()->content->contentTable;
			$fieldColumnPrefix = craft()->content->fieldColumnPrefix;

			if (craft()->db->columnExists($contentTable, $fieldColumnPrefix.$field->handle))
			{
				craft()->db->createCommand()->dropColumn($contentTable, $fieldColumnPrefix.$field->handle);
			}

			// Delete the row in fields
			$affectedRows = craft()->db->createCommand()->delete('fields', array('id' => $field->id));

			if ($affectedRows)
			{
				if ($fieldType)
				{
					$field->getFieldType()->onAfterDelete();
				}
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	// Layouts
	// -------------------------------------------------------------------------

	/**
	 * Returns a field layout by its ID.
	 *
	 * @param int $layoutId
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getLayoutById($layoutId)
	{
		if (!isset($this->_layoutsById) || !array_key_exists($layoutId, $this->_layoutsById))
		{
			$result = $this->_createLayoutQuery()
				->where('id = :id', array(':id' => $layoutId))
				->queryRow();

			if ($result)
			{
				$layout = new FieldLayoutModel($result);
			}
			else
			{
				$layout = null;
			}

			$this->_layoutsById[$layoutId] = $layout;
		}

		return $this->_layoutsById[$layoutId];
	}

	/**
	 * Returns a field layout by its type.
	 *
	 * @param string $type
	 *
	 * @return FieldLayoutModel
	 */
	public function getLayoutByType($type)
	{
		if (!isset($this->_layoutsByType) || !array_key_exists($type, $this->_layoutsByType))
		{
			$result = $this->_createLayoutQuery()
				->where('type = :type', array(':type' => $type))
				->queryRow();

			if ($result)
			{
				$id = $result['id'];

				if (!isset($this->_layoutsById[$id]))
				{
					$this->_layoutsById[$id] = new FieldLayoutModel($result);
				}

				$layout = $this->_layoutsById[$id];
			}
			else
			{
				$layout = new FieldLayoutModel();
			}

			$this->_layoutsByType[$type] = $layout;
		}

		return $this->_layoutsByType[$type];
	}

	/**
	 * Returns a layout's tabs by its ID.
	 *
	 * @param int $layoutId
	 *
	 * @return array
	 */
	public function getLayoutTabsById($layoutId)
	{
		$results = $this->_createLayoutTabQuery()
			->where('layoutId = :layoutId', array(':layoutId' => $layoutId))
			->queryAll();

		return FieldLayoutTabModel::populateModels($results);
	}

	/**
	 * Returns a layout's tabs by its ID.
	 *
	 * @param int $layoutId
	 *
	 * @return array
	 */
	public function getLayoutFieldsById($layoutId)
	{
		$results = $this->_createLayoutFieldQuery()
			->where('layoutId = :layoutId', array(':layoutId' => $layoutId))
			->queryAll();

		return FieldLayoutFieldModel::populateModels($results);
	}

	/**
	 * Assembles a field layout from post data.
	 *
	 * @return FieldLayoutModel
	 */
	public function assembleLayoutFromPost()
	{
		$postedFieldLayout = craft()->request->getPost('fieldLayout', array());
		$requiredFields = craft()->request->getPost('requiredFields', array());

		return $this->assembleLayout($postedFieldLayout, $requiredFields);
	}

	/**
	 * Assembles a field layout.
	 *
	 * @param array      $postedFieldLayout
	 * @param array|null $requiredFields
	 *
	 * @return FieldLayoutModel
	 */
	public function assembleLayout($postedFieldLayout, $requiredFields)
	{
		$tabs = array();
		$fields = array();

		$tabSortOrder = 0;

		foreach ($postedFieldLayout as $tabName => $fieldIds)
		{
			$tabFields = array();
			$tabSortOrder++;

			foreach ($fieldIds as $fieldSortOrder => $fieldId)
			{
				$field = new FieldLayoutFieldModel();
				$field->fieldId   = $fieldId;
				$field->required  = in_array($fieldId, $requiredFields);
				$field->sortOrder = ($fieldSortOrder+1);

				$fields[] = $field;
				$tabFields[] = $field;
			}

			$tab = new FieldLayoutTabModel();
			$tab->name      = urldecode($tabName);
			$tab->sortOrder = $tabSortOrder;
			$tab->setFields($tabFields);

			$tabs[] = $tab;
		}

		$layout = new FieldLayoutModel();
		$layout->setTabs($tabs);
		$layout->setFields($fields);

		return $layout;
	}

	/**
	 * Saves a field layout.
	 *
	 * @param FieldLayoutModel $layout
	 *
	 * @return bool
	 */
	public function saveLayout(FieldLayoutModel $layout)
	{
		// First save the layout
		$layoutRecord = new FieldLayoutRecord();
		$layoutRecord->type = $layout->type;
		$layoutRecord->save(false);
		$layout->id = $layoutRecord->id;

		foreach ($layout->getTabs() as $tab)
		{
			$tabRecord = new FieldLayoutTabRecord();
			$tabRecord->layoutId  = $layout->id;
			$tabRecord->name      = $tab->name;
			$tabRecord->sortOrder = $tab->sortOrder;
			$tabRecord->save(false);
			$tab->id = $tabRecord->id;

			foreach ($tab->getFields() as $field)
			{
				$fieldRecord = new FieldLayoutFieldRecord();
				$fieldRecord->layoutId  = $layout->id;
				$fieldRecord->tabId     = $tab->id;
				$fieldRecord->fieldId   = $field->fieldId;
				$fieldRecord->required  = $field->required;
				$fieldRecord->sortOrder = $field->sortOrder;
				$fieldRecord->save(false);
				$field->id = $fieldRecord->id;
			}
		}

		// Fire an 'onSaveFieldLayout' event
		$this->onSaveFieldLayout(new Event($this, array(
			'layout' => $layout,
		)));

		return true;
	}

	/**
	 * Deletes a field layout(s) by its ID.
	 *
	 * @param int|array $layoutId
	 *
	 * @return bool
	 */
	public function deleteLayoutById($layoutId)
	{
		if (!$layoutId)
		{
			return false;
		}

		if (is_array($layoutId))
		{
			$affectedRows = craft()->db->createCommand()->delete('fieldlayouts', array('in', 'id', $layoutId));
		}
		else
		{
			$affectedRows = craft()->db->createCommand()->delete('fieldlayouts', array('id' => $layoutId));
		}

		return (bool) $affectedRows;
	}

	/**
	 * Deletes field layouts of a given type.
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function deleteLayoutsByType($type)
	{
		$affectedRows = craft()->db->createCommand()->delete('fieldlayouts', array('type' => $type));
		return (bool) $affectedRows;
	}

	// Fieldtypes
	// -------------------------------------------------------------------------

	/**
	 * Returns all installed fieldtypes.
	 *
	 * @return array
	 */
	public function getAllFieldTypes()
	{
		return craft()->components->getComponentsByType(ComponentType::Field);
	}

	/**
	 * Gets a fieldtype.
	 *
	 * @param string $class
	 *
	 * @return BaseFieldType|null
	 */
	public function getFieldType($class)
	{
		return craft()->components->getComponentByTypeAndClass(ComponentType::Field, $class);
	}

	/**
	 * Populates a fieldtype by a field model.
	 *
	 * @param FieldModel            $field
	 * @param BaseElementModel|null $element
	 *
	 * @return BaseFieldType|null
	 */
	public function populateFieldType(FieldModel $field, $element = null)
	{
		$fieldType = craft()->components->populateComponentByTypeAndModel(ComponentType::Field, $field);

		if ($fieldType)
		{
			$fieldType->element = $element;
			return $fieldType;
		}
	}

	/**
	 * Fires an 'onSaveFieldLayout' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveFieldLayout(Event $event)
	{
		$this->raiseEvent('onSaveFieldLayout', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a DbCommand object prepped for retrieving groups.
	 *
	 * @return DbCommand
	 */
	private function _createGroupQuery()
	{
		return craft()->db->createCommand()
			->select('id, name')
			->from('fieldgroups')
			->order('name');
	}

	/**
	 * Returns a DbCommand object prepped for retrieving fields.
	 *
	 * @return DbCommand
	 */
	private function _createFieldQuery()
	{
		return craft()->db->createCommand()
			->select('id, groupId, name, handle, context, instructions, translatable, type, settings')
			->from('fields')
			->order('name');
	}

	/**
	 * Returns a DbCommand object prepped for retrieving layouts.
	 *
	 * @return DbCommand
	 */
	private function _createLayoutQuery()
	{
		return craft()->db->createCommand()
			->select('id, type')
			->from('fieldlayouts');
	}

	/**
	 * Returns a DbCommand object prepped for retrieving layout fields.
	 *
	 * @return DbCommand
	 */
	private function _createLayoutFieldQuery()
	{
		return craft()->db->createCommand()
			->select('id, layoutId, tabId, fieldId, required, sortOrder')
			->from('fieldlayoutfields')
			->order('sortOrder');
	}

	/**
	 * Returns a DbCommand object prepped for retrieving layout tabs.
	 *
	 * @return DbCommand
	 */
	private function _createLayoutTabQuery()
	{
		return craft()->db->createCommand()
			->select('id, layoutId, name, sortOrder')
			->from('fieldlayouttabs')
			->order('sortOrder');
	}

	/**
	 * Populates a field from its DB result.
	 *
	 * @param array $result
	 *
	 * @return FieldModel
	 */
	private function _populateField($result)
	{
		if ($result['settings'])
		{
			$result['settings'] = JsonHelper::decode($result['settings']);
		}

		return new FieldModel($result);
	}

	/**
	 * Gets a field group record or creates a new one.
	 *
	 * @param FieldGroupModel $group
	 *
	 * @throws Exception
	 * @return FieldGroupRecord
	 */
	private function _getGroupRecord(FieldGroupModel $group)
	{
		if ($group->id)
		{
			$groupRecord = FieldGroupRecord::model()->findById($group->id);

			if (!$groupRecord)
			{
				throw new Exception(Craft::t('No field group exists with the ID “{id}”.', array('id' => $group->id)));
			}
		}
		else
		{
			$groupRecord = new FieldGroupRecord();
		}

		return $groupRecord;
	}

	/**
	 * Returns a field record for a given model.
	 *
	 * @param FieldModel $field
	 *
	 * @throws Exception
	 * @return FieldRecord
	 */
	private function _getFieldRecord(FieldModel $field)
	{
		if (!$field->isNew())
		{
			$fieldId = $field->id;

			if (!isset($this->_fieldRecordsById) || !array_key_exists($fieldId, $this->_fieldRecordsById))
			{
				$this->_fieldRecordsById[$fieldId] = FieldRecord::model()->findById($fieldId);

				if (!$this->_fieldRecordsById[$fieldId])
				{
					throw new Exception(Craft::t('No field exists with the ID “{id}”.', array('id' => $fieldId)));
				}
			}

			return $this->_fieldRecordsById[$fieldId];
		}
		else
		{
			return new FieldRecord();
		}
	}
}
