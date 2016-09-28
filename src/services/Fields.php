<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\db\Query;
use craft\app\errors\FieldGroupNotFoundException;
use craft\app\errors\FieldNotFoundException;
use craft\app\errors\MissingComponentException;
use craft\app\events\FieldEvent;
use craft\app\events\FieldGroupEvent;
use craft\app\events\FieldLayoutEvent;
use craft\app\fields\Assets as AssetsField;
use craft\app\fields\Categories as CategoriesField;
use craft\app\fields\Checkboxes as CheckboxesField;
use craft\app\fields\Color as ColorField;
use craft\app\fields\Date as DateField;
use craft\app\fields\Dropdown as DropdownField;
use craft\app\fields\Entries as EntriesField;
use craft\app\fields\MissingField;
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
use craft\app\helpers\Component as ComponentHelper;
use craft\app\helpers\StringHelper;
use craft\app\models\FieldGroup;
use craft\app\models\FieldLayout;
use craft\app\models\FieldLayoutTab;
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
 * @since  3.0
 */
class Fields extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event FieldGroupEvent The event that is triggered before a field group is saved.
     */
    const EVENT_BEFORE_SAVE_FIELD_GROUP = 'beforeSaveFieldGroup';

    /**
     * @event FieldGroupEvent The event that is triggered after a field group is saved.
     */
    const EVENT_AFTER_SAVE_FIELD_GROUP = 'afterSaveFieldGroup';

    /**
     * @event FieldGroupEvent The event that is triggered before a field group is deleted.
     */
    const EVENT_BEFORE_DELETE_FIELD_GROUP = 'beforeDeleteFieldGroup';

    /**
     * @event FieldGroupEvent The event that is triggered after a field group is deleted.
     */
    const EVENT_AFTER_DELETE_FIELD_GROUP = 'afterDeleteFieldGroup';

    /**
     * @event FieldEvent The event that is triggered before a field is saved.
     */
    const EVENT_BEFORE_SAVE_FIELD = 'beforeSaveField';

    /**
     * @event FieldEvent The event that is triggered after a field is saved.
     */
    const EVENT_AFTER_SAVE_FIELD = 'afterSaveField';

    /**
     * @event FieldEvent The event that is triggered before a field is deleted.
     */
    const EVENT_BEFORE_DELETE_FIELD = 'beforeDeleteField';

    /**
     * @event FieldEvent The event that is triggered after a field is deleted.
     */
    const EVENT_AFTER_DELETE_FIELD = 'afterDeleteField';

    /**
     * @event FieldLayoutEvent The event that is triggered before a field layout is saved.
     */
    const EVENT_BEFORE_SAVE_FIELD_LAYOUT = 'beforeSaveFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered after a field layout is saved.
     */
    const EVENT_AFTER_SAVE_FIELD_LAYOUT = 'afterSaveFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered before a field layout is deleted.
     */
    const EVENT_BEFORE_DELETE_FIELD_LAYOUT = 'beforeDeleteFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered after a field layout is deleted.
     */
    const EVENT_AFTER_DELETE_FIELD_LAYOUT = 'afterDeleteFieldLayout';

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
     * @param string|null $indexBy The attribute to index the field groups by
     *
     * @return FieldGroup[] The field groups
     */
    public function getAllGroups($indexBy = null)
    {
        if (!$this->_fetchedAllGroups) {
            $this->_groupsById = [];

            $results = $this->_createGroupQuery()->all();

            foreach ($results as $result) {
                $group = new FieldGroup($result);
                $this->_groupsById[$group->id] = $group;
            }

            $this->_fetchedAllGroups = true;
        }

        if ($indexBy == 'id') {
            $groups = $this->_groupsById;
        } else if (!$indexBy) {
            $groups = array_values($this->_groupsById);
        } else {
            $groups = [];

            foreach ($this->_groupsById as $group) {
                $groups[$group->$indexBy] = $group;
            }
        }

        return $groups;
    }

    /**
     * Returns a field group by its ID.
     *
     * @param integer $groupId The field group’s ID
     *
     * @return FieldGroup|null The field group, or null if it doesn’t exist
     */
    public function getGroupById($groupId)
    {
        if (!isset($this->_groupsById) || !array_key_exists($groupId,
                $this->_groupsById)
        ) {
            $result = $this->_createGroupQuery()
                ->where('id = :id', [':id' => $groupId])
                ->one();

            if ($result) {
                $group = new FieldGroup($result);
            } else {
                $group = null;
            }

            $this->_groupsById[$groupId] = $group;
        }

        return $this->_groupsById[$groupId];
    }

    /**
     * Saves a field group.
     *
     * @param FieldGroup $group         The field group to be saved
     * @param boolean    $runValidation Whether the group should be validated
     *
     * @return boolean Whether the field group was saved successfully
     */
    public function saveGroup(FieldGroup $group, $runValidation = true)
    {
        if ($runValidation && !$group->validate()) {
            Craft::info('Field group not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewGroup = !$group->id;

        // Fire a 'beforeSaveFieldLayout' event
        $this->trigger(self::EVENT_BEFORE_SAVE_FIELD_GROUP, new FieldGroupEvent([
            'group' => $group,
            'isNew' => $isNewGroup,
        ]));

        $groupRecord = $this->_getGroupRecord($group);
        $groupRecord->name = $group->name;
        $groupRecord->save(false);

        // Now that we have an ID, save it on the model & models
        if ($isNewGroup) {
            $group->id = $groupRecord->id;
        }

        // Fire an 'afterSaveFieldLayout' event
        $this->trigger(self::EVENT_AFTER_SAVE_FIELD_GROUP, new FieldGroupEvent([
            'group' => $group,
            'isNew' => $isNewGroup,
        ]));

        return true;
    }

    /**
     * Deletes a field group by its ID.
     *
     * @param integer $groupId The field group’s ID
     *
     * @return boolean Whether the field group was deleted successfully
     */
    public function deleteGroupById($groupId)
    {
        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        return $this->deleteGroup($group);
    }

    /**
     * Deletes a field group.
     *
     * @param FieldGroup $group The field group
     *
     * @return boolean Whether the field group was deleted successfully
     */
    public function deleteGroup(FieldGroup $group)
    {
        /** @var FieldGroupRecord $groupRecord */
        $groupRecord = FieldGroupRecord::find()
            ->where(['id' => $group->id])
            ->with('fields')
            ->one();

        if (!$groupRecord) {
            return false;
        }

        // Fire a 'beforeDeleteFieldGroup' event
        $this->trigger(self::EVENT_BEFORE_DELETE_FIELD_GROUP, new FieldGroupEvent([
            'group' => $group
        ]));

        // Manually delete the fields (rather than relying on cascade deletes) so we have a chance to delete the
        // content columns
        /** @var Field[] $fields */
        $fields = $this->getFieldsByGroupId($group->id);

        foreach ($fields as $field) {
            $this->deleteField($field);
        }

        Craft::$app->getDb()->createCommand()
            ->delete('{{%fieldgroups}}', ['id' => $group->id])
            ->execute();

        // Delete our cache of it
        unset($this->_groupsById[$group->id]);

        // Fire an 'afterDeleteFieldGroup' event
        $this->trigger(self::EVENT_AFTER_DELETE_FIELD_GROUP, new FieldGroupEvent([
            'group' => $group
        ]));

        return true;
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
            AssetsField::class,
            CategoriesField::class,
            CheckboxesField::class,
            ColorField::class,
            DateField::class,
            DropdownField::class,
            EntriesField::class,
            LightswitchField::class,
            MatrixField::class,
            MultiSelectField::class,
            NumberField::class,
            PlainTextField::class,
            PositionSelectField::class,
            RadioButtonsField::class,
            RichTextField::class,
            TableField::class,
            TagsField::class,
            UsersField::class,
        ];

        foreach (Craft::$app->getPlugins()->call('getFieldTypes', [], true) as $pluginFieldTypes) {
            $fieldTypes = array_merge($fieldTypes, $pluginFieldTypes);
        }

        return $fieldTypes;
    }

    /**
     * Returns all field types that have a column in the content table.
     *
     * @return FieldInterface[] The field type classes
     */
    public function getFieldTypesWithContent()
    {
        $fieldTypes = [];

        foreach (static::getAllFieldTypes() as $fieldType) {
            if ($fieldType::hasContentColumn()) {
                $fieldTypes[] = $fieldType;
            }
        }

        return $fieldTypes;
    }

    /**
     * Creates a field with a given config.
     *
     * @param mixed $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @return FieldInterface The field
     */
    public function createField($config)
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {
            return ComponentHelper::createComponent($config, FieldInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();

            return MissingField::create($config);
        }
    }

    /**
     * Returns all fields within a field context(s).
     *
     * @param string|null          $indexBy The field property to index the resulting fields by
     * @param string|string[]|null $context The field context(s) to fetch fields from. Defaults to {@link ContentService::$fieldContext}.
     *
     * @return FieldInterface[] The fields
     */
    public function getAllFields($indexBy = null, $context = null)
    {
        if ($context === null) {
            $context = [Craft::$app->getContent()->fieldContext];
        } else if (!is_array($context)) {
            $context = [$context];
        }

        $missingContexts = [];

        foreach ($context as $c) {
            if (!isset($this->_allFieldsInContext[$c])) {
                $missingContexts[] = $c;
                $this->_allFieldsInContext[$c] = [];
            }
        }

        if (!empty($missingContexts)) {
            $rows = $this->_createFieldQuery()
                ->where(['in', 'fields.context', $missingContexts])
                ->all();

            foreach ($rows as $row) {
                /** @var Field $field */
                $field = $this->createField($row);

                $this->_allFieldsInContext[$field->context][] = $field;
                $this->_fieldsById[$field->id] = $field;
                $this->_fieldsByContextAndHandle[$field->context][$field->handle] = $field;
            }
        }

        $fields = [];

        foreach ($context as $c) {
            if (!$indexBy) {
                $fields = array_merge($fields, $this->_allFieldsInContext[$c]);
            } else {
                foreach ($this->_allFieldsInContext[$c] as $field) {
                    $fields[$field->$indexBy] = $field;
                }
            }
        }

        return $fields;
    }

    /**
     * Returns all fields that have a column in the content table.
     *
     * @return FieldInterface[] The fields
     */
    public function getFieldsWithContent()
    {
        $context = Craft::$app->getContent()->fieldContext;

        if (!isset($this->_fieldsWithContent[$context])) {
            $this->_fieldsWithContent[$context] = [];

            foreach ($this->getAllFields() as $field) {
                if ($field::hasContentColumn()) {
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
     *
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldById($fieldId)
    {
        if (!isset($this->_fieldsById) || !array_key_exists($fieldId,
                $this->_fieldsById)
        ) {
            $result = $this->_createFieldQuery()
                ->where('fields.id = :id', [':id' => $fieldId])
                ->one();

            if ($result) {
                /** @var Field $field */
                $field = $this->createField($result);

                $this->_fieldsById[$fieldId] = $field;
                $this->_fieldsByContextAndHandle[$field->context][$field->handle] = $field;
            } else {
                return null;
            }
        }

        return $this->_fieldsById[$fieldId];
    }

    /**
     * Returns a field by its handle.
     *
     * @param string $handle The field’s handle
     *
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByHandle($handle)
    {
        $context = Craft::$app->getContent()->fieldContext;

        if (!isset($this->_fieldsByContextAndHandle[$context]) || !array_key_exists($handle, $this->_fieldsByContextAndHandle[$context])) {
            // Guilty until proven innocent
            $this->_fieldsByContextAndHandle[$context][$handle] = null;

            if ($this->doesFieldWithHandleExist($handle, $context)) {
                $result = $this->_createFieldQuery()
                    ->where([
                        'and',
                        'fields.handle = :handle',
                        'fields.context = :context'
                    ], [':handle' => $handle, ':context' => $context])
                    ->one();

                if ($result) {
                    /** @var Field $field */
                    $field = $this->createField($result);
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
     * @param string      $handle  The field handle
     * @param string|null $context The field context (defauts to ContentService::$fieldContext)
     *
     * @return boolean Whether a field with that handle exists
     */
    public function doesFieldWithHandleExist($handle, $context = null)
    {
        if ($context === null) {
            $context = Craft::$app->getContent()->fieldContext;
        }

        if (!isset($this->_allFieldHandlesByContext)) {
            $this->_allFieldHandlesByContext = [];

            $results = (new Query())
                ->select('handle,context')
                ->from('{{%fields}}')
                ->all();

            foreach ($results as $result) {
                $this->_allFieldHandlesByContext[$result['context']][] = $result['handle'];
            }
        }

        return (isset($this->_allFieldHandlesByContext[$context]) && in_array($handle, $this->_allFieldHandlesByContext[$context]));
    }

    /**
     * Returns all the fields in a given group.
     *
     * @param integer     $groupId The field group’s ID
     * @param string|null $indexBy The attribute to index the fields by
     *
     * @return FieldInterface[] The fields
     */
    public function getFieldsByGroupId($groupId, $indexBy = null)
    {
        $results = $this->_createFieldQuery()
            ->where('fields.groupId = :groupId', [':groupId' => $groupId])
            ->all();

        $fields = [];

        foreach ($results as $result) {
            $field = $this->createField($result);

            if ($indexBy) {
                $fields[$field->$indexBy] = $field;
            } else {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Returns all of the fields used by a given element type.
     *
     * @param ElementInterface|string $elementType
     * @param string|null             $indexBy
     *
     * @return FieldInterface[] The fields
     */
    public function getFieldsByElementType($elementType, $indexBy = null)
    {
        $results = $this->_createFieldQuery()
            ->innerJoin('{{%fieldlayoutfields}} flf', 'flf.fieldId = fields.id')
            ->innerJoin('{{%fieldlayouts}} fl', 'fl.id = flf.layoutId')
            ->where(['fl.type' => $elementType])
            ->all();

        $fields = [];

        foreach ($results as $result) {
            $field = $this->createField($result);

            if ($indexBy) {
                $fields[$field->$indexBy] = $field;
            } else {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Saves a field.
     *
     * @param FieldInterface $field         The Field to be saved
     * @param boolean        $runValidation Whether the field should be validated
     *
     * @return boolean Whether the field was saved successfully
     * @throws \Exception if reasons
     */
    public function saveField(FieldInterface $field, $runValidation = true)
    {
        /** @var Field $field */
        // Set the field context if it's not set
        if (!$field->context) {
            $field->context = Craft::$app->getContent()->fieldContext;
        }

        if ($runValidation && !$field->validate()) {
            Craft::info('Field not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewField = $field->getIsNew();

        // Fire a 'beforeSaveField' event
        $this->trigger(self::EVENT_BEFORE_SAVE_FIELD, new FieldEvent([
            'field' => $field,
            'isNew' => $isNewField,
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$field->beforeSave()) {
                $transaction->rollBack();

                return false;
            }

            $fieldRecord = $this->_getFieldRecord($field);

            // Create/alter the content table column
            $contentTable = Craft::$app->getContent()->contentTable;
            $oldColumnName = $this->oldFieldColumnPrefix.$fieldRecord->getOldHandle();
            $newColumnName = Craft::$app->getContent()->fieldColumnPrefix.$field->handle;

            if ($field::hasContentColumn()) {
                $columnType = $field->getContentColumnType();

                // Make sure we're working with the latest data in the case of a renamed field.
                Craft::$app->getDb()->schema->refresh();

                if (Craft::$app->getDb()->columnExists($contentTable, $oldColumnName)) {
                    Craft::$app->getDb()->createCommand()
                        ->alterColumn($contentTable, $oldColumnName, $columnType, $newColumnName)
                        ->execute();
                } else if (Craft::$app->getDb()->columnExists($contentTable, $newColumnName)) {
                    Craft::$app->getDb()->createCommand()
                        ->alterColumn($contentTable, $newColumnName, $columnType)
                        ->execute();
                } else {
                    Craft::$app->getDb()->createCommand()
                        ->addColumnBefore($contentTable, $newColumnName, $columnType, 'dateCreated')
                        ->execute();
                }

                // Clear the translation key format if not using a custom translation method
                if ($field->translationMethod != Field::TRANSLATION_METHOD_CUSTOM) {
                    $field->translationKeyFormat = null;
                }
            } else {
                // Did the old field have a column we need to remove?
                if (!$isNewField) {
                    if ($fieldRecord->getOldHandle() && Craft::$app->getDb()->columnExists($contentTable,
                            $oldColumnName)
                    ) {
                        Craft::$app->getDb()->createCommand()
                            ->dropColumn($contentTable, $oldColumnName)
                            ->execute();
                    }
                }

                // Fields without a content column don't get translated
                $field->translationMethod = Field::TRANSLATION_METHOD_NONE;
                $field->translationKeyFormat = null;
            }

            $fieldRecord->groupId = $field->groupId;
            $fieldRecord->name = $field->name;
            $fieldRecord->handle = $field->handle;
            $fieldRecord->context = $field->context;
            $fieldRecord->instructions = $field->instructions;
            $fieldRecord->translationMethod = $field->translationMethod;
            $fieldRecord->translationKeyFormat = $field->translationKeyFormat;
            $fieldRecord->type = $field->getType();
            $fieldRecord->settings = $field->getSettings();

            $fieldRecord->save(false);

            // Now that we have a field ID, save it on the model
            if ($isNewField) {
                $field->id = $fieldRecord->id;
            } else {
                // Save the old field handle on the model in case the field type needs to do something with it.
                $field->oldHandle = $fieldRecord->getOldHandle();

                unset($this->_fieldsByContextAndHandle[$field->context][$field->oldHandle]);

                if (
                    isset($this->_allFieldHandlesByContext[$field->context]) &&
                    $field->oldHandle != $field->handle &&
                    ($oldHandleIndex = array_search($field->oldHandle, $this->_allFieldHandlesByContext[$field->context])) !== false
                ) {
                    array_splice($this->_allFieldHandlesByContext[$field->context], $oldHandleIndex, 1);
                }
            }

            // Cache it
            $this->_fieldsById[$field->id] = $field;
            $this->_fieldsByContextAndHandle[$field->context][$field->handle] = $field;

            if (isset($this->_allFieldHandlesByContext)) {
                $this->_allFieldHandlesByContext[$field->context][] = $field->handle;
            }

            unset($this->_allFieldsInContext[$field->context]);
            unset($this->_fieldsWithContent[$field->context]);

            $field->afterSave();

            // Update the field version
            if ($field->context === 'global') {
                $this->_updateFieldVersion();
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveField' event
        $this->trigger(self::EVENT_AFTER_SAVE_FIELD, new FieldEvent([
            'field' => $field,
            'isNew' => $isNewField,
        ]));

        return true;
    }

    /**
     * Deletes a field by its ID.
     *
     * @param integer $fieldId The field’s ID
     *
     * @return boolean Whether the field was deleted successfully
     */
    public function deleteFieldById($fieldId)
    {
        $field = $this->getFieldById($fieldId);

        if (!$field) {
            return false;
        }

        return $this->deleteField($field);
    }

    /**
     * Deletes a field.
     *
     * @param FieldInterface $field The field
     *
     * @return boolean Whether the field was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteField(FieldInterface $field)
    {
        /** @var Field $field */
        // Fire a 'beforeDeleteField' event
        $this->trigger(self::EVENT_BEFORE_DELETE_FIELD, new FieldEvent([
            'field' => $field,
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$field->beforeDelete()) {
                $transaction->rollBack();

                return false;
            }

            // De we need to delete the content column?
            $contentTable = Craft::$app->getContent()->contentTable;
            $fieldColumnPrefix = Craft::$app->getContent()->fieldColumnPrefix;

            if (Craft::$app->getDb()->columnExists($contentTable, $fieldColumnPrefix.$field->handle)) {
                Craft::$app->getDb()->createCommand()
                    ->dropColumn($contentTable, $fieldColumnPrefix.$field->handle)
                    ->execute();
            }

            // Delete the row in fields
            Craft::$app->getDb()->createCommand()
                ->delete('{{%fields}}', ['id' => $field->id])
                ->execute();

            $field->afterDelete();

            if ($field->context === 'global') {
                $this->_updateFieldVersion();
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteField' event
        $this->trigger(self::EVENT_AFTER_DELETE_FIELD, new FieldEvent([
            'field' => $field,
        ]));

        return true;
    }

    // Layouts
    // -------------------------------------------------------------------------

    /**
     * Returns a field layout by its ID.
     *
     * @param integer $layoutId The field layout’s ID
     *
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutById($layoutId)
    {
        if (!isset($this->_layoutsById) || !array_key_exists($layoutId,
                $this->_layoutsById)
        ) {
            $result = $this->_createLayoutQuery()
                ->where('id = :id', [':id' => $layoutId])
                ->one();

            if ($result) {
                $layout = new FieldLayout($result);
            } else {
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
     *
     * @return FieldLayout The field layout
     */
    public function getLayoutByType($type)
    {
        if (!isset($this->_layoutsByType) || !array_key_exists($type,
                $this->_layoutsByType)
        ) {
            $result = $this->_createLayoutQuery()
                ->where('type = :type', [':type' => $type])
                ->one();

            if ($result) {
                $id = $result['id'];

                if (!isset($this->_layoutsById[$id])) {
                    $this->_layoutsById[$id] = new FieldLayout($result);
                }

                $layout = $this->_layoutsById[$id];
            } else {
                $layout = new FieldLayout();
            }

            $this->_layoutsByType[$type] = $layout;
        }

        return $this->_layoutsByType[$type];
    }

    /**
     * Returns a layout's tabs by its ID.
     *
     * @param integer $layoutId The field layout’s ID
     *
     * @return FieldLayoutTab[] The field layout’s tabs
     */
    public function getLayoutTabsById($layoutId)
    {
        $tabs = $this->_createLayoutTabQuery()
            ->where('layoutId = :layoutId', [':layoutId' => $layoutId])
            ->all();

        foreach ($tabs as $key => $value) {
            $tabs[$key] = FieldLayoutTab::create($value);
        }

        return $tabs;
    }

    /**
     * Returns the fields in a field layout, identified by its ID.
     *
     * @param integer $layoutId The field layout’s ID
     *
     * @return FieldInterface[] The fields
     */
    public function getFieldsByLayoutId($layoutId)
    {
        $fields = $this->_createFieldQuery()
            ->addSelect([
                'flf.layoutId',
                'flf.tabId',
                'flf.required',
                'flf.sortOrder'
            ])
            ->innerJoin('{{%fieldlayoutfields}} flf', 'flf.fieldId = fields.id')
            ->innerJoin('{{%fieldlayouttabs}} flt', 'flt.id = flf.tabId')
            ->where(['flf.layoutId' => $layoutId])
            ->orderBy('flt.sortOrder, flf.sortOrder')
            ->all();

        foreach ($fields as $key => $config) {
            $fields[$key] = $this->createField($config);
        }

        return $fields;
    }

    /**
     * Assembles a field layout from post data.
     *
     * @param string|null $namespace The namespace that the form data was posted in, if any
     *
     * @return FieldLayout The field layout
     */
    public function assembleLayoutFromPost($namespace = null)
    {
        $paramPrefix = ($namespace ? rtrim($namespace, '.').'.' : '');
        $postedFieldLayout = Craft::$app->getRequest()->getBodyParam($paramPrefix.'fieldLayout', []);
        $requiredFields = Craft::$app->getRequest()->getBodyParam($paramPrefix.'requiredFields', []);

        return $this->assembleLayout($postedFieldLayout, $requiredFields);
    }

    /**
     * Assembles a field layout.
     *
     * @param array $postedFieldLayout The post data for the field layout
     * @param array $requiredFields    The field IDs that should be marked as required in the field layout
     *
     * @return FieldLayout The field layout
     */
    public function assembleLayout($postedFieldLayout, $requiredFields = [])
    {
        $tabs = [];
        $fields = [];

        $tabSortOrder = 0;

        // Get all the fields
        $allFieldIds = [];

        foreach ($postedFieldLayout as $fieldIds) {
            $allFieldIds = array_merge($allFieldIds, $fieldIds);
        }

        if ($allFieldIds) {
            $allFieldsById = $this->_createFieldQuery()
                ->where(['in', 'id', $allFieldIds])
                ->indexBy('id')
                ->all();

            foreach ($allFieldsById as $id => $field) {
                $allFieldsById[$id] = $this->createField($field);
            }
        }

        foreach ($postedFieldLayout as $tabName => $fieldIds) {
            $tabFields = [];
            $tabSortOrder++;

            foreach ($fieldIds as $fieldSortOrder => $fieldId) {
                if (!isset($allFieldsById[$fieldId])) {
                    continue;
                }

                $field = $allFieldsById[$fieldId];
                $field->required = in_array($fieldId, $requiredFields);
                $field->sortOrder = ($fieldSortOrder + 1);

                $fields[] = $field;
                $tabFields[] = $field;
            }

            $tab = new FieldLayoutTab();
            $tab->name = urldecode($tabName);
            $tab->sortOrder = $tabSortOrder;
            $tab->setFields($tabFields);

            $tabs[] = $tab;
        }

        $layout = new FieldLayout();
        $layout->setTabs($tabs);
        $layout->setFields($fields);

        return $layout;
    }

    /**
     * Saves a field layout.
     *
     * @param FieldLayout $layout        The field layout
     * @param boolean     $runValidation Whether the layout should be validated
     *
     * @return boolean Whether the field layout was saved successfully
     */
    public function saveLayout(FieldLayout $layout, $runValidation = true)
    {
        if ($runValidation && !$layout->validate()) {
            Craft::info('Field layout not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewLayout = !$layout->id;

        // Fire a 'beforeSaveFieldLayout' event
        $this->trigger(self::EVENT_BEFORE_SAVE_FIELD_LAYOUT, new FieldLayoutEvent([
            'layout' => $layout,
            'isNew' => $isNewLayout,
        ]));

        // First save the layout
        $layoutRecord = new FieldLayoutRecord();
        $layoutRecord->type = $layout->type;
        $layoutRecord->save(false);

        if ($isNewLayout) {
            $layout->id = $layoutRecord->id;
        }

        foreach ($layout->getTabs() as $tab) {
            $tabRecord = new FieldLayoutTabRecord();
            $tabRecord->layoutId = $layout->id;
            $tabRecord->name = $tab->name;
            $tabRecord->sortOrder = $tab->sortOrder;
            $tabRecord->save(false);
            $tab->id = $tabRecord->id;

            foreach ($tab->getFields() as $field) {
                /** @var Field $field */
                $fieldRecord = new FieldLayoutFieldRecord();
                $fieldRecord->layoutId = $layout->id;
                $fieldRecord->tabId = $tab->id;
                $fieldRecord->fieldId = $field->id;
                $fieldRecord->required = $field->required;
                $fieldRecord->sortOrder = $field->sortOrder;
                $fieldRecord->save(false);
            }
        }

        // Fire an 'afterSaveFieldLayout' event
        $this->trigger(self::EVENT_AFTER_SAVE_FIELD_LAYOUT, new FieldLayoutEvent([
            'layout' => $layout,
            'isNew' => $isNewLayout,
        ]));

        return true;
    }

    /**
     * Deletes a field layout(s) by its ID.
     *
     * @param integer|integer[] $layoutId The field layout’s ID
     *
     * @return boolean Whether the field layout was deleted successfully
     */
    public function deleteLayoutById($layoutId)
    {
        if (!$layoutId) {
            return false;
        }

        if (!is_array($layoutId)) {
            $layoutId = [$layoutId];
        }

        foreach ($layoutId as $thisLayoutId) {
            $layout = $this->getLayoutById($thisLayoutId);

            if ($layout) {
                $this->deleteLayout($layout);
            }
        }

        return true;
    }

    /**
     * Deletes a field layout.
     *
     * @param FieldLayout $layout The field layout
     *
     * @return boolean Whether the field layout was deleted successfully
     */
    public function deleteLayout(FieldLayout $layout)
    {
        // Fire a 'beforeDeleteFieldLayout' event
        $this->trigger(self::EVENT_BEFORE_DELETE_FIELD_LAYOUT, new FieldLayoutEvent([
            'layout' => $layout
        ]));

        Craft::$app->getDb()->createCommand()
            ->delete('{{%fieldlayouts}}', ['id' => $layout->id])
            ->execute();

        $this->trigger(self::EVENT_AFTER_DELETE_FIELD_LAYOUT, new FieldLayoutEvent([
            'layout' => $layout
        ]));

        return true;
    }

    /**
     * Deletes field layouts associated with a given element type.
     *
     * @param string $type The element type
     *
     * @return boolean Whether the field layouts were deleted successfully
     */
    public function deleteLayoutsByType($type)
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete('{{%fieldlayouts}}', ['type' => $type])
            ->execute();

        return (bool)$affectedRows;
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
                'fields.translationMethod',
                'fields.translationKeyFormat',
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
     * @param FieldGroup $group
     *
     * @return FieldGroupRecord
     * @throws FieldGroupNotFoundException if $group->id is invalid
     */
    private function _getGroupRecord(FieldGroup $group)
    {
        if ($group->id) {
            $groupRecord = FieldGroupRecord::findOne($group->id);

            if (!$groupRecord) {
                throw new FieldGroupNotFoundException("No field group exists with the ID '{$group->id}'");
            }
        } else {
            $groupRecord = new FieldGroupRecord();
        }

        return $groupRecord;
    }

    /**
     * Returns a field record for a given model.
     *
     * @param FieldInterface $field
     *
     * @return FieldRecord
     * @throws FieldNotFoundException if $field->id is invalid
     */
    private function _getFieldRecord(FieldInterface $field)
    {
        /** @var Field $field */
        if (!$field->getIsNew()) {
            if (!isset($this->_fieldRecordsById) || !array_key_exists($field->id,
                    $this->_fieldRecordsById)
            ) {
                $this->_fieldRecordsById[$field->id] = FieldRecord::findOne($field->id);

                if (!$this->_fieldRecordsById[$field->id]) {
                    throw new FieldNotFoundException("No field exists with the ID '{$field->id}'");
                }
            }

            return $this->_fieldRecordsById[$field->id];
        }

        return new FieldRecord();
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
