<?php
namespace Craft;

/**
 * Class FieldsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
	private $_allFieldHandlesByContext;

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
	 * Returns all fields within a field context(s).
	 *
	 * @param string|null          $indexBy The field property to index the resulting fields by
	 * @param string|string[]|null $context The field context(s) to fetch fields from. Defaults to {@link ContentService::$fieldContext}.
	 *
	 * @return FieldModel[] The resulting fields
	 */
	public function getAllFields($indexBy = null, $context = null)
	{
		if ($context === null)
		{
			$context = array(craft()->content->fieldContext);
		}
		else if (!is_array($context))
		{
			$context = array($context);
		}

		$missingContexts = array();

		foreach ($context as $c)
		{
			if (!isset($this->_allFieldsInContext[$c]))
			{
				$missingContexts[] = $c;
				$this->_allFieldsInContext[$c] = array();
			}
		}

		if (!empty($missingContexts))
		{
			$rows = $this->_createFieldQuery()
				->where(array('in', 'f.context', $missingContexts))
				->queryAll();

			foreach ($rows as $row)
			{
				$field = $this->_populateField($row);

				$this->_allFieldsInContext[$row['context']][] = $field;
				$this->_fieldsById[$field->id] = $field;
				$this->_fieldsByContextAndHandle[$row['context']][$field->handle] = $field;
			}
		}

		$fields = array();

		foreach ($context as $c)
		{
			if (!$indexBy)
			{
				$fields = array_merge($fields, $this->_allFieldsInContext[$c]);
			}
			else
			{
				foreach ($this->_allFieldsInContext[$c] as $field)
				{
					$fields[$field->$indexBy] = $field;
				}
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
				->where('f.id = :id', array(':id' => $fieldId))
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
			// Guilty until proven innocent
			$this->_fieldsByContextAndHandle[$context][$handle] = null;

			if ($this->doesFieldWithHandleExist($handle, $context))
			{
				$result = $this->_createFieldQuery()
					->where(array('and', 'f.handle = :handle', 'f.context = :context'), array(':handle' => $handle, ':context' => $context))
					->queryRow();

				if ($result)
				{
					$field = $this->_populateField($result);
					$this->_fieldsById[$field->id] = $field;
					$this->_fieldsByContextAndHandle[$context][$field->handle] = $field;
				}
			}
		}

		return $this->_fieldsByContextAndHandle[$context][$handle];
	}

	/**
	 * Returns whether a field exists with a given handle and context.
	 *
	 * @param string $handle The field handle
	 * @param string|null $context The field context (defauts to ContentService::$fieldContext)
	 *
	 * @return bool Whether a field with that handle exists
	 */
	public function doesFieldWithHandleExist($handle, $context = null)
	{
		if ($context === null)
		{
			$context = craft()->content->fieldContext;
		}

		if (!isset($this->_allFieldHandlesByContext))
		{
			$this->_allFieldHandlesByContext = array();

			$results = craft()->db->createCommand()
				->select('handle,context')
				->from('fields')
				->queryAll();

			foreach ($results as $result)
			{
				$this->_allFieldHandlesByContext[$result['context']][] = $result['handle'];
			}
		}

		return (isset($this->_allFieldHandlesByContext[$context]) && in_array($handle, $this->_allFieldHandlesByContext[$context]));
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
			->where('f.groupId = :groupId', array(':groupId' => $groupId))
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
	 * Returns all of the fields used by a given element type.
	 *
	 * @param string      $elementTypeClass
	 * @param string|null $indexBy
	 *
	 * @return FieldModel[]
	 */
	public function getFieldsByElementType($elementTypeClass, $indexBy = null)
	{
		$results = $this->_createFieldQuery()
			->join('fieldlayoutfields flf', 'flf.fieldId = f.id')
			->join('fieldlayouts fl', 'fl.id = flf.layoutId')
			->where('fl.type = :type', array(':type' => $elementTypeClass))
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

				// Get the field type
				$fieldType = $field->getFieldType();

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

				$fieldRecord->groupId      = $field->groupId;
				$fieldRecord->name         = $field->name;
				$fieldRecord->handle       = $field->handle;
				$fieldRecord->context      = $field->context;
				$fieldRecord->instructions = $field->instructions;
				$fieldRecord->translatable = $field->translatable;
				$fieldRecord->type         = $field->type;

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

				if (!$isNewField)
				{
					// Save the old field handle on the model in case the field type needs to do something with it.
					$field->oldHandle = $fieldRecord->getOldHandle();

					unset($this->_fieldsByContextAndHandle[$field->context][$field->oldHandle]);

					if (
						isset($this->_allFieldHandlesByContext[$field->context]) &&
						$field->oldHandle != $field->handle &&
						($oldHandleIndex = array_search($field->oldHandle, $this->_allFieldHandlesByContext[$field->context])) !== false
					)
					{
						array_splice($this->_allFieldHandlesByContext[$field->context], $oldHandleIndex, 1);
					}
				}

				// Cache it
				$this->_fieldsById[$field->id] = $field;
				$this->_fieldsByContextAndHandle[$field->context][$field->handle] = $field;

				if (isset($this->_allFieldHandlesByContext))
				{
					$this->_allFieldHandlesByContext[$field->context][] = $field->handle;
				}

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
	 * Returns a layout's fields by its ID.
	 *
	 * @param int $layoutId
	 *
	 * @return array
	 */
	public function getLayoutFieldsById($layoutId)
	{
		$results = $this->_createLayoutFieldQuery($layoutId)->queryAll();

		return FieldLayoutFieldModel::populateModels($results);
	}

	/**
	 * Returns a layout's fields by its ID, in the layout-defined sort order.
	 *
	 * @param int $layoutId
	 *
	 * @return array
	 */
	public function getOrderedLayoutFieldsById($layoutId)
	{
		$results = $this->_createLayoutFieldQuery($layoutId)
			->join('fieldlayouttabs fieldlayouttabs', 'fieldlayouttabs.id = fieldlayoutfields.tabId')
			->order('fieldlayouttabs.sortOrder, fieldlayoutfields.sortOrder')
			->queryAll();

		return FieldLayoutFieldModel::populateModels($results);
	}

	/**
	 * Assembles a field layout from post data.
	 *
	 * @param string|null $namespace The namespace that the form data was posted in, if any.
	 *
	 * @return FieldLayoutModel
	 */
	public function assembleLayoutFromPost($namespace = null)
	{
		$paramPrefix = ($namespace ? rtrim($namespace, '.').'.' : '');
		$postedFieldLayout = craft()->request->getPost($paramPrefix.'fieldLayout', array());
		$requiredFields = craft()->request->getPost($paramPrefix.'requiredFields', array());

		return $this->assembleLayout($postedFieldLayout, $requiredFields);
	}

	/**
	 * Assembles a field layout.
	 *
	 * @param array $postedFieldLayout
	 * @param array $requiredFields
	 *
	 * @return FieldLayoutModel
	 */
	public function assembleLayout($postedFieldLayout, $requiredFields = array())
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
			->select('f.id, f.groupId, f.name, f.handle, f.context, f.instructions, f.translatable, f.type, f.settings')
			->from('fields f')
			->order('f.name');
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
	 * @param int $layoutId
	 *
	 * @return DbCommand
	 */
	private function _createLayoutFieldQuery($layoutId)
	{
		return craft()->db->createCommand()
			->select('fieldlayoutfields.id, fieldlayoutfields.layoutId, fieldlayoutfields.tabId, fieldlayoutfields.fieldId, fieldlayoutfields.required, fieldlayoutfields.sortOrder')
			->from('fieldlayoutfields fieldlayoutfields')
			->where('fieldlayoutfields.layoutId = :layoutId', array(':layoutId' => $layoutId));
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
