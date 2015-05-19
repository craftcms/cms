<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\db\Query;
use craft\app\errors\Exception;
use craft\app\errors\InvalidComponentException;
use craft\app\events\FieldLayoutEvent;
use craft\app\fields\Assets as AssetsField;
use craft\app\fields\Categories as CategoriesField;
use craft\app\fields\Checkboxes as CheckboxesField;
use craft\app\fields\Color as ColorField;
use craft\app\fields\Date as DateField;
use craft\app\fields\Dropdown as DropdownField;
use craft\app\fields\Entries as EntriesField;
use craft\app\fields\InvalidField;
use craft\app\fields\Lightswitch as LightswitchField;
use craft\app\fields\Matrix as MatrixField;
use craft\app\fields\MultiSelect as MultiSelectField;
use craft\app\fields\Number as NumberField;
use craft\app\fields\PlainText as PlainTextField;
use craft\app\fields\PositionSelect as PositionSelectField;
use craft\app\fields\RadioButtons as RadioButtonsField;
use craft\app\fields\RichText as RichTextField;
use craft\app\fields\Table as TableField;
use craft\app\fields\Tags as TagsField;
use craft\app\fields\Users as UsersField;
use craft\app\helpers\ComponentHelper;
use craft\app\helpers\StringHelper;
use craft\app\models\FieldGroup as FieldGroupModel;
use craft\app\models\FieldLayout as FieldLayoutModel;
use craft\app\models\FieldLayoutTab as FieldLayoutTabModel;
use craft\app\records\Field as FieldRecord;
use craft\app\records\FieldGroup as FieldGroupRecord;
use craft\app\records\FieldLayout as FieldLayoutRecord;
use craft\app\records\FieldLayoutField as FieldLayoutFieldRecord;
use craft\app\records\FieldLayoutTab as FieldLayoutTabRecord;
use yii\base\Component;

/**
 * Class Fields service.
 *
 * An instance of the Fields service is globally accessible in Craft via [[Application::fields `Craft::$app->getFields()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Fields extends Component
{
	// Constants
	// =========================================================================

	/**
	 * @var string The field interface name
	 */
	const FIELD_INTERFACE = 'craft\app\base\FieldInterface';

	/**
     * @event FieldLayoutEvent The event that is triggered after a field layout is saved.
     */
    const EVENT_AFTER_SAVE_FIELD_LAYOUT = 'afterSaveFieldLayout';

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
	 * @param string|null $indexBy The attribute to index the field groups by
	 * @return FieldGroupModel[] The field groups
	 */
	public function getAllGroups($indexBy = null)
	{
		if (!$this->_fetchedAllGroups)
		{
			$this->_groupsById = [];

			$results = $this->_createGroupQuery()->all();

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
			$groups = [];

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
	 * @param integer $groupId The field group’s ID
	 * @return FieldGroupModel|null The field group, or null if it doesn’t exist
	 */
	public function getGroupById($groupId)
	{
		if (!isset($this->_groupsById) || !array_key_exists($groupId, $this->_groupsById))
		{
			$result = $this->_createGroupQuery()
				->where('id = :id', [':id' => $groupId])
				->one();

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
	 * @param FieldGroupModel $group The field group to be saved
	 * @return boolean Whether the field group was saved successfully
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
	 * Deletes a field group by its ID.
	 *
	 * @param integer $groupId The field group’s ID
	 * @return boolean Whether the field group was deleted successfully
	 */
	public function deleteGroupById($groupId)
	{
		/** @var FieldGroupRecord $groupRecord */
		$groupRecord = FieldGroupRecord::find()
			->where(['id' => $groupId])
			->with('fields')
			->one();

		if (!$groupRecord)
		{
			return false;
		}

		// Manually delete the fields (rather than relying on cascade deletes) so we have a chance to delete the
		// content columns
		/** @var FieldRecord $fieldRecord */
		foreach ($groupRecord->getFields() as $fieldRecord)
		{
			$field = $this->createField($fieldRecord);
			$this->deleteField($field);
		}

		$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%fieldgroups}}', ['id' => $groupId])->execute();
		return (bool) $affectedRows;
	}

	// Fields
	// -------------------------------------------------------------------------

	/**
	 * Returns all available field type classes.
	 *
	 * @return FieldInterface[] The available field type classes
	 */
	public function getAllFieldTypes()
	{
		$fieldTypes = [
			AssetsField::className(),
			CategoriesField::className(),
			CheckboxesField::className(),
			ColorField::className(),
			DateField::className(),
			DropdownField::className(),
			EntriesField::className(),
			LightswitchField::className(),
			MatrixField::className(),
			MultiSelectField::className(),
			NumberField::className(),
			PlainTextField::className(),
			PositionSelectField::className(),
			RadioButtonsField::className(),
			RichTextField::className(),
			TableField::className(),
			TagsField::className(),
			UsersField::className(),
		];

		foreach (Craft::$app->getPlugins()->call('getFieldTypes', [], true) as $pluginFieldTypes)
		{
			$fieldTypes = array_merge($fieldTypes, $pluginFieldTypes);
		}

		return $fieldTypes;
	}

	/**
	 * Creates a field with a given config.
	 *
	 * @param mixed $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
	 * @return FieldInterface|Field The field
	 */
	public function createField($config)
	{
		if (is_string($config))
		{
			$config = ['type' => $config];
		}

		try
		{
			return ComponentHelper::createComponent($config, self::FIELD_INTERFACE);
		}
		catch (InvalidComponentException $e)
		{
			$config['errorMessage'] = $e->getMessage();
			return InvalidField::create($config);
		}
	}

	/**
	 * Returns all fields.
	 *
	 * @param string|null $indexBy The attribute to index the fields by
	 * @return FieldInterface[]|Field[] The fields
	 */
	public function getAllFields($indexBy = null)
	{
		$context = Craft::$app->getContent()->fieldContext;

		if (!isset($this->_allFieldsInContext[$context]))
		{
			$results = $this->_createFieldQuery()
				->where('context = :context', [':context' => $context])
				->all();

			$this->_allFieldsInContext[$context] = [];

			foreach ($results as $result)
			{
				$field = $this->createField($result);

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
			$fields = [];

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
	 * @return FieldInterface[]|Field[] The fields
	 */
	public function getFieldsWithContent()
	{
		$context = Craft::$app->getContent()->fieldContext;

		if (!isset($this->_fieldsWithContent[$context]))
		{
			$this->_fieldsWithContent[$context] = [];

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
	 * @param integer $fieldId The field’s ID
	 * @return FieldInterface|Field|null The field, or null if it doesn’t exist
	 */
	public function getFieldById($fieldId)
	{
		if (!isset($this->_fieldsById) || !array_key_exists($fieldId, $this->_fieldsById))
		{
			$result = $this->_createFieldQuery()
				->where('id = :id', [':id' => $fieldId])
				->one();

			if ($result)
			{
				$field = $this->createField($result);

				$this->_fieldsById[$fieldId] = $field;
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
	 * @param string $handle The field’s handle
	 * @return FieldInterface|Field|null The field, or null if it doesn’t exist
	 */
	public function getFieldByHandle($handle)
	{
		$context = Craft::$app->getContent()->fieldContext;

		if (!isset($this->_fieldsByContextAndHandle[$context]) || !array_key_exists($handle, $this->_fieldsByContextAndHandle[$context]))
		{
			$result = $this->_createFieldQuery()
				->where(['and', 'handle = :handle', 'context = :context'], [':handle' => $handle, ':context' => $context])
				->one();

			if ($result)
			{
				$field = $this->createField($result);
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
	 * @param integer     $groupId The field group’s ID
	 * @param string|null $indexBy The attribute to index the fields by
	 * @return FieldInterface[]|Field[] The fields
	 */
	public function getFieldsByGroupId($groupId, $indexBy = null)
	{
		$results = $this->_createFieldQuery()
			->where('groupId = :groupId', [':groupId' => $groupId])
			->all();

		$fields = [];

		foreach ($results as $result)
		{
			$field = $this->createField($result);

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
	 * Saves a field.
	 *
	 * @param FieldInterface|Field $field    The Field to be saved
	 * @param boolean              $validate Whether the field should be validated first
	 * @return boolean Whether the field was saved successfully
	 * @throws \Exception
	 */
	public function saveField(FieldInterface $field, $validate = true)
	{
		if ((!$validate || $field->validate()) && $field->beforeSave())
		{
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
			try
			{
				$field->context = Craft::$app->getContent()->fieldContext;

				$fieldRecord = $this->_getFieldRecord($field);
				$isNewField = $fieldRecord->getIsNewRecord();

				$fieldRecord->groupId      = $field->groupId;
				$fieldRecord->name         = $field->name;
				$fieldRecord->handle       = $field->handle;
				$fieldRecord->context      = $field->context;
				$fieldRecord->instructions = $field->instructions;
				$fieldRecord->translatable = $field->translatable;
				$fieldRecord->type         = $field->getType();
				$fieldRecord->settings     = $field->getSettings();

				$fieldRecord->save(false);

				// Now that we have a field ID, save it on the model
				if ($isNewField)
				{
					$field->id = $fieldRecord->id;
				}

				// Create/alter the content table column
				$contentTable = Craft::$app->getContent()->contentTable;
				$oldColumnName = $this->oldFieldColumnPrefix.$fieldRecord->getOldHandle();
				$newColumnName = Craft::$app->getContent()->fieldColumnPrefix.$field->handle;

				if ($field::hasContentColumn())
				{
					$columnType = $field->getContentColumnType();

					// Make sure we're working with the latest data in the case of a renamed field.
					Craft::$app->getDb()->schema->refresh();

					if (Craft::$app->getDb()->columnExists($contentTable, $oldColumnName))
					{
						Craft::$app->getDb()->createCommand()->alterColumn($contentTable, $oldColumnName, $columnType, $newColumnName)->execute();
					}
					else if (Craft::$app->getDb()->columnExists($contentTable, $newColumnName))
					{
						Craft::$app->getDb()->createCommand()->alterColumn($contentTable, $newColumnName, $columnType)->execute();
					}
					else
					{
						Craft::$app->getDb()->createCommand()->addColumnBefore($contentTable, $newColumnName, $columnType, 'dateCreated')->execute();
					}
				}
				else
				{
					// Did the old field have a column we need to remove?
					if (!$isNewField)
					{
						if ($fieldRecord->getOldHandle() && Craft::$app->getDb()->columnExists($contentTable, $oldColumnName))
						{
							Craft::$app->getDb()->createCommand()->dropColumn($contentTable, $oldColumnName)->execute();
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

				$field->afterSave();

				// Update the field version
				if ($field->context === 'global')
				{
					$this->_updateFieldVersion();
				}

				if ($transaction !== null)
				{
					$transaction->commit();
				}

				return true;
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
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a field by its ID.
	 *
	 * @param integer $fieldId The field’s ID
	 * @return boolean Whether the field was deleted successfully
	 */
	public function deleteFieldById($fieldId)
	{
		/** @var FieldRecord $fieldRecord */
		$fieldRecord = FieldRecord::findOne($fieldId);

		if (!$fieldRecord)
		{
			return false;
		}

		$field = $this->createField($fieldRecord);
		return $this->deleteField($field);
	}

	/**
	 * Deletes a field.
	 *
	 * @param FieldInterface $field The field
	 * @return boolean Whether the field was deleted successfully
	 * @throws \Exception
	 */
	public function deleteField(FieldInterface $field)
	{
		if (!$field->beforeDelete())
		{
			return false;
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// De we need to delete the content column?
			$contentTable = Craft::$app->getContent()->contentTable;
			$fieldColumnPrefix = Craft::$app->getContent()->fieldColumnPrefix;

			if (Craft::$app->getDb()->columnExists($contentTable, $fieldColumnPrefix.$field->handle))
			{
				Craft::$app->getDb()->createCommand()->dropColumn($contentTable, $fieldColumnPrefix.$field->handle)->execute();
			}

			// Delete the row in fields
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%fields}}', ['id' => $field->id])->execute();

			if ($affectedRows)
			{
				$field->afterDelete();
			}

			if ($field->context === 'global')
			{
				$this->_updateFieldVersion();
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
	 * @param integer $layoutId The field layout’s ID
	 * @return FieldLayoutModel|null The field layout, or null if it doesn’t exist
	 */
	public function getLayoutById($layoutId)
	{
		if (!isset($this->_layoutsById) || !array_key_exists($layoutId, $this->_layoutsById))
		{
			$result = $this->_createLayoutQuery()
				->where('id = :id', [':id' => $layoutId])
				->one();

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
	 * Returns a field layout by its associated element type.
	 *
	 * @param string $type The associated element type
	 * @return FieldLayoutModel The field layout
	 */
	public function getLayoutByType($type)
	{
		if (!isset($this->_layoutsByType) || !array_key_exists($type, $this->_layoutsByType))
		{
			$result = $this->_createLayoutQuery()
				->where('type = :type', [':type' => $type])
				->one();

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
	 * @param integer $layoutId The field layout’s ID
	 * @return FieldLayoutTabModel[] The field layout’s tabs
	 */
	public function getLayoutTabsById($layoutId)
	{
		$tabs = $this->_createLayoutTabQuery()
			->where('layoutId = :layoutId', [':layoutId' => $layoutId])
			->all();

		foreach ($tabs as $key => $value)
		{
			$tabs[$key] = FieldLayoutTabModel::create($value);
		}

		return $tabs;
	}

	/**
	 * Returns the fields in a field layout, identified by its ID.
	 *
	 * @param integer $layoutId The field layout’s ID
	 * @return FieldInterface[]|Field[] The fields
	 */
	public function getFieldsByLayoutId($layoutId)
	{
		$fields = $this->_createFieldQuery()
			->addSelect(['flf.layoutId', 'flf.tabId', 'flf.required', 'flf.sortOrder'])
			->innerJoin('{{%fieldlayoutfields}} flf', 'flf.fieldId = fields.id')
			->innerJoin('{{%fieldlayouttabs}} flt', 'flt.id = flf.tabId')
			->where(['flf.layoutId' => $layoutId])
			->orderBy('flt.sortOrder, flf.sortOrder')
			->all();

		foreach ($fields as $key => $config)
		{
			$fields[$key] = $this->createField($config);
		}

		return $fields;
	}

	/**
	 * Assembles a field layout from post data.
	 *
	 * @return FieldLayoutModel The field layout
	 */
	public function assembleLayoutFromPost()
	{
		$postedFieldLayout = Craft::$app->getRequest()->getBodyParam('fieldLayout', []);
		$requiredFields = Craft::$app->getRequest()->getBodyParam('requiredFields', []);

		return $this->assembleLayout($postedFieldLayout, $requiredFields);
	}

	/**
	 * Assembles a field layout.
	 *
	 * @param array      $postedFieldLayout The post data for the field layout
	 * @param array|null $requiredFields    The field IDs that should be marked as required in the field layout
	 * @return FieldLayoutModel The field layout
	 */
	public function assembleLayout($postedFieldLayout, $requiredFields)
	{
		$tabs   = [];
		$fields = [];

		$tabSortOrder = 0;

		// Get all the fields
		$allFieldIds = [];

		foreach ($postedFieldLayout as $fieldIds)
		{
			$allFieldIds = array_merge($allFieldIds, $fieldIds);
		}

		if ($allFieldIds)
		{
			$allFieldsById = $this->_createFieldQuery()
				->where(['in', 'id', $allFieldIds])
				->indexBy('id')
				->all();

			foreach ($allFieldsById as $id => $field)
			{
				$allFieldsById[$id] = $this->createField($field);
			}
		}

		foreach ($postedFieldLayout as $tabName => $fieldIds)
		{
			$tabFields = [];
			$tabSortOrder++;

			foreach ($fieldIds as $fieldSortOrder => $fieldId)
			{
				if (!isset($allFieldsById[$fieldId]))
				{
					continue;
				}

				$field = $allFieldsById[$fieldId];
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
	 * @param FieldLayoutModel $layout The field layout
	 * @return boolean Whether the field layout was saved successfully
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
				$fieldRecord->fieldId   = $field->id;
				$fieldRecord->required  = $field->required;
				$fieldRecord->sortOrder = $field->sortOrder;
				$fieldRecord->save(false);
			}
		}

		// Fire an 'afterSaveFieldLayout' event
		$this->trigger(static::EVENT_AFTER_SAVE_FIELD_LAYOUT, new FieldLayoutEvent([
			'layout' => $layout
		]));

		return true;
	}

	/**
	 * Deletes a field layout(s) by its ID.
	 *
	 * @param int|array $layoutId The field layout’s ID
	 * @return boolean Whether the field layout was deleted successfully
	 */
	public function deleteLayoutById($layoutId)
	{
		if (!$layoutId)
		{
			return false;
		}

		if (is_array($layoutId))
		{
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%fieldlayouts}}', ['in', 'id', $layoutId])->execute();
		}
		else
		{
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%fieldlayouts}}', ['id' => $layoutId])->execute();
		}

		return (bool) $affectedRows;
	}

	/**
	 * Deletes field layouts associated with a given element type.
	 *
	 * @param string $type The element type
	 * @return boolean Whether the field layouts were deleted successfully
	 */
	public function deleteLayoutsByType($type)
	{
		$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%fieldlayouts}}', ['type' => $type])->execute();
		return (bool) $affectedRows;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a Query object prepped for retrieving groups.
	 *
	 * @return Query
	 */
	private function _createGroupQuery()
	{
		return (new Query())
			->select(['id', 'name'])
			->from('{{%fieldgroups}}')
			->orderBy('name');
	}

	/**
	 * Returns a Query object prepped for retrieving fields.
	 *
	 * @return Query
	 */
	private function _createFieldQuery()
	{
		return (new Query())
			->select([
				'fields.id',
				'fields.groupId',
				'fields.name',
				'fields.handle',
				'fields.context',
				'fields.instructions',
				'fields.translatable',
				'fields.type',
				'fields.settings'
			])
			->from('{{%fields}} fields')
			->orderBy('fields.name');
	}

	/**
	 * Returns a Query object prepped for retrieving layouts.
	 *
	 * @return Query
	 */
	private function _createLayoutQuery()
	{
		return (new Query)
			->select(['id', 'type'])
			->from('{{%fieldlayouts}}');
	}

	/**
	 * Returns a Query object prepped for retrieving layout fields.
	 *
	 * @return Query
	 */
	private function _createLayoutFieldQuery()
	{
		return (new Query())
			->select(['id', 'layoutId', 'tabId', 'fieldId', 'required', 'sortOrder'])
			->from('{{%fieldlayoutfields}}')
			->orderBy('sortOrder');
	}

	/**
	 * Returns a Query object prepped for retrieving layout tabs.
	 *
	 * @return Query
	 */
	private function _createLayoutTabQuery()
	{
		return (new Query())
			->select(['id', 'layoutId', 'name', 'sortOrder'])
			->from('{{%fieldlayouttabs}}')
			->orderBy('sortOrder');
	}

	/**
	 * Gets a field group record or creates a new one.
	 *
	 * @param FieldGroupModel $group
	 * @return FieldGroupRecord
	 * @throws Exception
	 */
	private function _getGroupRecord(FieldGroupModel $group)
	{
		if ($group->id)
		{
			$groupRecord = FieldGroupRecord::findOne($group->id);

			if (!$groupRecord)
			{
				throw new Exception(Craft::t('app', 'No field group exists with the ID “{id}”.', ['id' => $group->id]));
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
	 * @param FieldInterface $field
	 * @return FieldRecord
	 * @throws Exception
	 */
	private function _getFieldRecord(FieldInterface $field)
	{
		if (!$field->isNew())
		{
			$fieldId = $field->id;

			if (!isset($this->_fieldRecordsById) || !array_key_exists($fieldId, $this->_fieldRecordsById))
			{
				$this->_fieldRecordsById[$fieldId] = FieldRecord::findOne($fieldId);

				if (!$this->_fieldRecordsById[$fieldId])
				{
					throw new Exception(Craft::t('app', 'No field exists with the ID “{id}”.', ['id' => $fieldId]));
				}
			}

			return $this->_fieldRecordsById[$fieldId];
		}
		else
		{
			return new FieldRecord();
		}
	}

	/**
	 * Increases the app's field version, so the ContentBehavior (et al) classes get regenerated.
	 */
	private function _updateFieldVersion()
	{
		$info = Craft::$app->getInfo();
		$info->fieldVersion = StringHelper::randomString(12);
		Craft::$app->saveInfo($info);
	}
}
