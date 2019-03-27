<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\behaviors\ContentBehavior;
use craft\behaviors\ElementQueryBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\errors\FieldNotFoundException;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\events\FieldGroupEvent;
use craft\events\FieldLayoutEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fields\Assets as AssetsField;
use craft\fields\Categories as CategoriesField;
use craft\fields\Checkboxes as CheckboxesField;
use craft\fields\Color as ColorField;
use craft\fields\Date as DateField;
use craft\fields\Dropdown as DropdownField;
use craft\fields\Email as EmailField;
use craft\fields\Entries as EntriesField;
use craft\fields\Lightswitch as LightswitchField;
use craft\fields\Matrix as MatrixField;
use craft\fields\MissingField;
use craft\fields\MultiSelect as MultiSelectField;
use craft\fields\Number as NumberField;
use craft\fields\PlainText as PlainTextField;
use craft\fields\RadioButtons as RadioButtonsField;
use craft\fields\Table as TableField;
use craft\fields\Tags as TagsField;
use craft\fields\Url as UrlField;
use craft\fields\Users as UsersField;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\FieldGroup;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\records\Field as FieldRecord;
use craft\records\FieldGroup as FieldGroupRecord;
use craft\records\FieldLayout as FieldLayoutRecord;
use craft\records\FieldLayoutField as FieldLayoutFieldRecord;
use craft\records\FieldLayoutTab as FieldLayoutTabRecord;
use yii\base\Component;
use yii\base\Exception;

/**
 * Fields service.
 * An instance of the Fields service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getFields()|`Craft::$app->fields`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Fields extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering field types.
     *
     * Field types must implement [[FieldInterface]]. [[Field]] provides a base implementation.
     *
     * See [Field Types](https://docs.craftcms.com/v3/field-types.html) for documentation on creating field types.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Fields;
     * use yii\base\Event;
     *
     * Event::on(Fields::class,
     *     Fields::EVENT_REGISTER_FIELD_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyFieldType::class;
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_FIELD_TYPES = 'registerFieldTypes';

    /**
     * @event FieldGroupEvent The event that is triggered before a field group is saved.
     */
    const EVENT_BEFORE_SAVE_FIELD_GROUP = 'beforeSaveFieldGroup';

    /**
     * @event FieldGroupEvent The event that is triggered after a field group is saved.
     */
    const EVENT_AFTER_SAVE_FIELD_GROUP = 'afterSaveFieldGroup';

    /**
     * @event FieldGroupEvent The event that is triggered before a field group delete is applied to the database.
     */
    const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

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
     * @event FieldEvent The event that is triggered before a field delete is applied to the database.
     */
    const EVENT_BEFORE_APPLY_FIELD_DELETE = 'beforeApplyFieldDelete';

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

    const CONFIG_FIELDGROUP_KEY = 'fieldGroups';
    const CONFIG_FIELDS_KEY = 'fields';

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $oldFieldColumnPrefix = 'field_';

    /**
     * @var bool Whether to ignore changes to the project config.
     * @deprecated in 3.1.2. Use [[\craft\services\ProjectConfig::$muteEvents]] instead.
     */
    public $ignoreProjectConfigChanges = false;

    /**
     * @var FieldGroup[]
     */
    private $_groups;

    /**
     * @var Field[]
     */
    private $_fields;

    /**
     * @var
     */
    private $_layoutsById;

    /**
     * @var
     */
    private $_layoutsByType;

    /**
     * @var array
     */
    private $_savingFields = [];

    // Public Methods
    // =========================================================================

    // Groups
    // -------------------------------------------------------------------------

    /**
     * Returns all field groups.
     *
     * @return FieldGroup[] The field groups
     */
    public function getAllGroups(): array
    {
        if ($this->_groups !== null) {
            return $this->_groups;
        }

        $this->_groups = [];
        $results = $this->_createGroupQuery()->all();

        foreach ($results as $result) {
            $this->_groups[] = new FieldGroup($result);
        }

        return $this->_groups;
    }

    /**
     * Returns a field group by its ID.
     *
     * @param int $groupId The field group’s ID
     * @return FieldGroup|null The field group, or null if it doesn’t exist
     */
    public function getGroupById(int $groupId)
    {
        return ArrayHelper::firstWhere($this->getAllGroups(), 'id', $groupId);
    }

    /**
     * Saves a field group.
     *
     * @param FieldGroup $group The field group to be saved
     * @param bool $runValidation Whether the group should be validated
     * @return bool Whether the field group was saved successfully
     */
    public function saveGroup(FieldGroup $group, bool $runValidation = true): bool
    {
        $isNewGroup = !$group->id;

        // Fire a 'beforeSaveFieldGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FIELD_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FIELD_GROUP, new FieldGroupEvent([
                'group' => $group,
                'isNew' => $isNewGroup,
            ]));
        }

        if ($runValidation && !$group->validate()) {
            Craft::info('Field group not saved due to validation error.', __METHOD__);
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $configData = [
            'name' => $group->name
        ];

        if ($isNewGroup) {
            $group->uid = StringHelper::UUID();
        } else if (!$group->uid) {
            $group->uid = Db::uidById(Table::FIELDGROUPS, $group->id);
        }

        $projectConfig->set(self::CONFIG_FIELDGROUP_KEY . '.' . $group->uid, $configData);

        if ($isNewGroup) {
            $group->id = Db::idByUid(Table::FIELDGROUPS, $group->uid);
        }

        return true;
    }

    /**
     * Handle field group change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedGroup(ConfigEvent $event)
    {
        if ($this->ignoreProjectConfigChanges) {
            return;
        }

        $data = $event->newValue;
        $uid = $event->tokenMatches[0];

        $groupRecord = $this->_getGroupRecord($uid);
        $isNewGroup = $groupRecord->getIsNewRecord();

        // If this is a new group, set the UID we want.
        if ($isNewGroup) {
            $groupRecord->uid = $uid;
        }

        $groupRecord->name = $data['name'];
        $groupRecord->save(false);

        // Update caches
        $this->_groups = null;

        // Fire an 'afterSaveFieldGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_FIELD_GROUP, new FieldGroupEvent([
                'group' => $this->getGroupById($groupRecord->id),
                'isNew' => $isNewGroup,
            ]));
        }
    }

    /**
     * Handle field group getting deleted.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedGroup(ConfigEvent $event)
    {
        if ($this->ignoreProjectConfigChanges) {
            return;
        }

        $uid = $event->tokenMatches[0];
        $groupRecord = $this->_getGroupRecord($uid);

        if ($groupRecord->getIsNewRecord()) {
            return;
        }

        $group = $this->getGroupById($groupRecord->id);

        // Fire a 'beforeApplyGroupDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new FieldGroupEvent([
                'group' => $group,
            ]));
        }

        $groupRecord->delete();

        // Update caches
        $this->_groups = null;

        // Fire an 'afterDeleteFieldGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FIELD_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FIELD_GROUP, new FieldGroupEvent([
                'group' => $group
            ]));
        }
    }

    /**
     * Deletes a field group by its ID.
     *
     * @param int $groupId The field group’s ID
     * @return bool Whether the field group was deleted successfully
     */
    public function deleteGroupById(int $groupId): bool
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
     * @return bool Whether the field group was deleted successfully
     */
    public function deleteGroup(FieldGroup $group): bool
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
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_FIELD_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_FIELD_GROUP, new FieldGroupEvent([
                'group' => $group
            ]));
        }

        // Manually delete the fields (rather than relying on cascade deletes) so we have a chance to delete the
        // content columns
        /** @var Field[] $fields */
        $fields = $this->getFieldsByGroupId($group->id);

        foreach ($fields as $field) {
            $this->deleteField($field);
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_FIELDGROUP_KEY . '.' . $group->uid);
        return true;
    }

    // Fields
    // -------------------------------------------------------------------------

    /**
     * Returns all available field type classes.
     *
     * @return string[] The available field type classes
     */
    public function getAllFieldTypes(): array
    {
        $fieldTypes = [
            AssetsField::class,
            CategoriesField::class,
            CheckboxesField::class,
            ColorField::class,
            DateField::class,
            DropdownField::class,
            EmailField::class,
            EntriesField::class,
            LightswitchField::class,
            MatrixField::class,
            MultiSelectField::class,
            NumberField::class,
            PlainTextField::class,
            RadioButtonsField::class,
            TableField::class,
            TagsField::class,
            UrlField::class,
            UsersField::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $fieldTypes
        ]);
        $this->trigger(self::EVENT_REGISTER_FIELD_TYPES, $event);

        return $event->types;
    }

    /**
     * Returns all field types that have a column in the content table.
     *
     * @return string[] The field type classes
     */
    public function getFieldTypesWithContent(): array
    {
        $fieldTypes = [];

        foreach ($this->getAllFieldTypes() as $fieldType) {
            /** @var Field|string $fieldType */
            if ($fieldType::hasContentColumn()) {
                $fieldTypes[] = $fieldType;
            }
        }

        return $fieldTypes;
    }

    /**
     * Returns all field types whose column types are considered compatible with a given field.
     *
     * @param FieldInterface $field The current field to base compatible fields on
     * @param bool $includeCurrent Whether $field's class should be included
     * @return string[] The compatible field type classes
     */
    public function getCompatibleFieldTypes(FieldInterface $field, bool $includeCurrent = true): array
    {
        /** @var Field $field */
        if (!$field::hasContentColumn()) {
            return $includeCurrent ? [get_class($field)] : [];
        }

        // If the field has any validation errors and has an ID, swap it with the saved field
        if (!$field->getIsNew() && $field->hasErrors()) {
            $field = $this->getFieldById($field->id);
        }

        $types = [];
        $fieldColumnType = $field->getContentColumnType();

        foreach ($this->getAllFieldTypes() as $class) {
            if ($class === get_class($field)) {
                if ($includeCurrent) {
                    $types[] = $class;
                }
                continue;
            }

            if (!$class::hasContentColumn()) {
                continue;
            }

            /** @var FieldInterface $tempField */
            $tempField = new $class();
            if (!Db::areColumnTypesCompatible($fieldColumnType, $tempField->getContentColumnType())) {
                continue;
            }

            $types[] = $class;
        }

        // Make sure the current field class is in there if it's supposed to be
        if ($includeCurrent && !in_array(get_class($field), $types, true)) {
            $types[] = get_class($field);
        }

        return $types;
    }

    /**
     * Creates a field with a given config.
     *
     * @param mixed $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return FieldInterface The field
     */
    public function createField($config): FieldInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (!empty($config['id']) && empty($config['uid']) && is_numeric($config['id'])) {
            $uid = Db::uidById(Table::FIELDS, $config['id']);
            $config['uid'] = $uid;
        }

        try {
            /** @var Field $field */
            $field = ComponentHelper::createComponent($config, FieldInterface::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $field = new MissingField($config);
        }

        return $field;
    }

    /**
     * Returns all fields within a field context(s).
     *
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to {@link ContentService::$fieldContext}.
     * Set to `false` to get all fields regardless of context.
     * @return FieldInterface[] The fields
     */
    public function getAllFields($context = null): array
    {
        if ($this->_fields === null) {
            $this->_fields = [];
            $results = $this->_createFieldQuery()->all();
            foreach ($results as $result) {
                $this->_fields[] = $this->createField($result);
            }
        }

        if ($context === false) {
            return $this->_fields;
        }

        if ($context === null) {
            $context = Craft::$app->getContent()->fieldContext;
        }

        if (is_string($context)) {
            return ArrayHelper::filterByValue($this->_fields, 'context', $context, true);
        }

        return ArrayHelper::filterByValue($this->_fields, function(FieldInterface $field) use ($context) {
            /** @var Field $field */
            return in_array($field->context, $context, true);
        });
    }

    /**
     * Returns all fields that have a column in the content table.
     *
     * @return FieldInterface[] The fields
     */
    public function getFieldsWithContent(): array
    {
        return ArrayHelper::filterByValue($this->getAllFields(), function(FieldInterface $field) {
            return $field::hasContentColumn();
        });
    }

    /**
     * Returns a field by its ID.
     *
     * @param int $fieldId The field’s ID
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldById(int $fieldId)
    {
        return ArrayHelper::firstWhere($this->getAllFields(false), 'id', $fieldId);
    }

    /**
     * Returns a field by its UID.
     *
     * @param string $fieldUid The field’s UID
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByUid(string $fieldUid)
    {
        return ArrayHelper::firstWhere($this->getAllFields(false), 'uid', $fieldUid, true);
    }

    /**
     * Returns a field by its handle.
     *
     * ---
     *
     * ```php
     * $body = Craft::$app->fields->getFieldByHandle('body');
     * ```
     * ```twig
     * {% set body = craft.app.fields.getFieldByHandle('body') %}
     * {{ body.instructions }}
     * ```
     *
     * @param string $handle The field’s handle
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByHandle(string $handle)
    {
        return ArrayHelper::firstWhere($this->getAllFields(), 'handle', $handle, true);
    }

    /**
     * Returns whether a field exists with a given handle and context.
     *
     * @param string $handle The field handle
     * @param string|null $context The field context (defauts to ContentService::$fieldContext)
     * @return bool Whether a field with that handle exists
     */
    public function doesFieldWithHandleExist(string $handle, string $context = null): bool
    {
        return ArrayHelper::firstWhere($this->getAllFields($context), 'handle', $handle, true) !== null;
    }

    /**
     * Returns all the fields in a given group.
     *
     * @param int $groupId The field group’s ID
     * @return FieldInterface[] The fields
     */
    public function getFieldsByGroupId(int $groupId): array
    {
        return ArrayHelper::filterByValue($this->getAllFields(false), 'groupId', $groupId);
    }

    /**
     * Returns all of the fields used by a given element type.
     *
     * @param string $elementType
     * @return FieldInterface[] The fields
     */
    public function getFieldsByElementType(string $elementType): array
    {
        $results = $this->_createFieldQuery()
            ->innerJoin('{{%fieldlayoutfields}} flf', '[[flf.fieldId]] = [[fields.id]]')
            ->innerJoin('{{%fieldlayouts}} fl', '[[fl.id]] = [[flf.layoutId]]')
            ->where([
                'fl.type' => $elementType,
                'fl.dateDeleted' => null,
            ])
            ->all();

        $fields = [];

        foreach ($results as $result) {
            $fields[] = $this->createField($result);
        }

        return $fields;
    }

    /**
     * Creates a field config array for the given field.
     *
     * @param FieldInterface $field
     * @return array
     */
    public function createFieldConfig(FieldInterface $field): array
    {
        /** @var Field $field */
        $config = [
            'name' => $field->name,
            'handle' => $field->handle,
            'instructions' => $field->instructions,
            'searchable' => $field->searchable,
            'translationMethod' => $field->translationMethod,
            'translationKeyFormat' => $field->translationKeyFormat,
            'type' => get_class($field),
            'settings' => $field->getSettings(),
            'contentColumnType' => $field->getContentColumnType(),
        ];

        if ($field->groupId) {
            $config['fieldGroup'] = $this->getGroupById($field->groupId)->uid;
        } else {
            $config['fieldGroup'] = null;
        }

        return $config;
    }

    /**
     * Saves a field.
     *
     * @param FieldInterface $field The Field to be saved
     * @param bool $runValidation Whether the field should be validated
     * @return bool Whether the field was saved successfully
     * @throws \Throwable if reasons
     */
    public function saveField(FieldInterface $field, bool $runValidation = true): bool
    {
        /** @var Field $field */
        $isNewField = $field->getIsNew();

        // Fire a 'beforeSaveField' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FIELD)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FIELD, new FieldEvent([
                'field' => $field,
                'isNew' => $isNewField,
            ]));
        }

        if (!$field->beforeSave($isNewField)) {
            return false;
        }

        if ($runValidation && !$field->validate()) {
            Craft::info('Field not saved due to validation error.', __METHOD__);
            return false;
        }

        $this->prepFieldForSave($field);
        $configData = $this->createFieldConfig($field);

        // Only store field data in the project config for global context
        if ($field->context === 'global') {
            $configPath = self::CONFIG_FIELDS_KEY . '.' . $field->uid;
            Craft::$app->getProjectConfig()->set($configPath, $configData);
        } else {
            // Otherwise just save it to the DB
            $this->applyFieldSave($field->uid, $configData, $field->context);
        }

        if ($isNewField) {
            $field->id = Db::idByUid(Table::FIELDS, $field->uid);
        }

        return true;
    }

    /**
     * Preps a field to be saved.
     *
     * @param FieldInterface $field
     */
    public function prepFieldForSave(FieldInterface $field)
    {
        /** @var Field $field */
        // Clear the translation key format if not using a custom translation method
        if ($field->translationMethod !== Field::TRANSLATION_METHOD_CUSTOM) {
            $field->translationKeyFormat = null;
        }

        // Make sure it's got a UUID
        if ($field->getIsNew()) {
            if (empty($field->uid)) {
                $field->uid = StringHelper::UUID();
            }
        } else if (!$field->uid) {
            $field->uid = Db::uidById(Table::FIELDS, $field->id);
        }

        // Store with all the populated data for future reference.
        $this->_savingFields[$field->uid] = $field;
    }

    /**
     * Handle field changes.
     *
     * @param ConfigEvent $event
     * @throws \Throwable
     */
    public function handleChangedField(ConfigEvent $event)
    {
        if ($this->ignoreProjectConfigChanges) {
            return;
        }

        $data = $event->newValue;
        $fieldUid = $event->tokenMatches[0];

        $this->applyFieldSave($fieldUid, $data, 'global');
    }

    /**
     * Deletes a field by its ID.
     *
     * @param int $fieldId The field’s ID
     * @return bool Whether the field was deleted successfully
     */
    public function deleteFieldById(int $fieldId): bool
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
     * @return bool Whether the field was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteField(FieldInterface $field): bool
    {
        /** @var Field $field */
        // Fire a 'beforeDeleteField' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_FIELD)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_FIELD, new FieldEvent([
                'field' => $field,
            ]));
        }

        if (!$field->beforeDelete()) {
            return false;
        }

        if ($field->context === 'global') {
            Craft::$app->getProjectConfig()->remove(self::CONFIG_FIELDS_KEY . '.' . $field->uid);
        } else {
            $this->applyFieldDelete($field->uid);
        }

        return true;
    }

    /**
     * Handle a field getting deleted.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedField(ConfigEvent $event)
    {
        if ($this->ignoreProjectConfigChanges) {
            return;
        }

        $fieldUid = $event->tokenMatches[0];

        $this->applyFieldDelete($fieldUid);
    }

    /**
     * Applies a field delete to the database.
     *
     * @param $fieldUid
     * @throws \Throwable if database error
     */
    public function applyFieldDelete($fieldUid)
    {
        try {
            $fieldRecord = $this->_getFieldRecord($fieldUid);
        } catch (FieldNotFoundException $exception) {
            return;
        }

        if (!$fieldRecord->id) {
            return;
        }

        /** @var Field $field */
        $field = $this->getFieldById($fieldRecord->id);

        // Fire a 'beforeApplyFieldDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_FIELD_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_FIELD_DELETE, new FieldEvent([
                'field' => $field,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $field->beforeApplyDelete();

            // De we need to delete the content column?
            $contentTable = Craft::$app->getContent()->contentTable;
            $fieldColumnPrefix = Craft::$app->getContent()->fieldColumnPrefix;

            if (Craft::$app->getDb()->columnExists($contentTable, $fieldColumnPrefix . $fieldRecord->handle)) {
                Craft::$app->getDb()->createCommand()
                    ->dropColumn($contentTable, $fieldColumnPrefix . $fieldRecord->handle)
                    ->execute();
            }

            // Delete the row in fields
            Craft::$app->getDb()->createCommand()
                ->delete(Table::FIELDS, ['id' => $fieldRecord->id])
                ->execute();

            $field->afterDelete();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_fields = null;

        // Update the field version
        $this->updateFieldVersion();

        // Fire an 'afterDeleteField' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FIELD)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FIELD, new FieldEvent([
                'field' => $field,
            ]));
        }
    }

    /**
     * Refreshes the internal field cache.
     *
     * This should be called whenever a field is updated or deleted directly in
     * the database, rather than going through this service.
     */
    public function refreshFields()
    {
        $this->_fields = null;
        $this->updateFieldVersion();
    }

    // Layouts
    // -------------------------------------------------------------------------

    /**
     * Returns a field layout by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutById(int $layoutId)
    {
        if ($this->_layoutsById !== null && array_key_exists($layoutId, $this->_layoutsById)) {
            return $this->_layoutsById[$layoutId];
        }

        $result = $this->_createLayoutQuery()
            ->andWhere(['id' => $layoutId])
            ->one();

        return $this->_layoutsById[$layoutId] = $result ? new FieldLayout($result) : null;
    }

    /**
     * Returns a field layout by its associated element type.
     *
     * @param string $type The associated element type
     * @return FieldLayout The field layout
     */
    public function getLayoutByType(string $type): FieldLayout
    {
        if ($this->_layoutsByType !== null && array_key_exists($type, $this->_layoutsByType)) {
            return $this->_layoutsByType[$type];
        }

        $result = $this->_createLayoutQuery()
            ->andWhere(['type' => $type])
            ->one();

        if (!$result) {
            return $this->_layoutsByType[$type] = new FieldLayout();
        }

        $id = $result['id'];
        if (!isset($this->_layoutsById[$id])) {
            $this->_layoutsById[$id] = new FieldLayout($result);
        }

        return $this->_layoutsByType[$type] = $this->_layoutsById[$id];
    }

    /**
     * Returns a layout's tabs by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @return FieldLayoutTab[] The field layout’s tabs
     */
    public function getLayoutTabsById(int $layoutId): array
    {
        $tabs = $this->_createLayoutTabQuery()
            ->where(['layoutId' => $layoutId])
            ->all();

        $isMysql = Craft::$app->getDb()->getIsMysql();

        foreach ($tabs as $key => $value) {
            if ($isMysql) {
                $value['name'] = html_entity_decode($value['name'], ENT_QUOTES | ENT_HTML5);
            }
            $tabs[$key] = new FieldLayoutTab($value);
        }

        return $tabs;
    }

    /**
     * Returns the field IDs grouped by layout IDs, for a given set of layout IDs.
     *
     * @param int[] $layoutIds The field layout IDs
     * @return array
     */
    public function getFieldIdsByLayoutIds(array $layoutIds): array
    {
        $results = (new Query())
            ->select(['flf.layoutId', 'fields.id'])
            ->from(['{{%fields}} fields'])
            ->innerJoin('{{%fieldlayoutfields}} flf', '[[flf.fieldId]] = [[fields.id]]')
            ->where(['flf.layoutId' => $layoutIds])
            ->all();

        $fieldIdsByLayoutId = [];
        foreach ($results as $result) {
            $fieldIdsByLayoutId[$result['layoutId']][] = $result['id'];
        }

        return $fieldIdsByLayoutId;
    }

    /**
     * Returns the fields in a field layout, identified by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @return FieldInterface[] The fields
     */
    public function getFieldsByLayoutId(int $layoutId): array
    {
        $fields = [];

        $results = $this->_createFieldQuery()
            ->addSelect([
                'flf.layoutId',
                'flf.tabId',
                'flf.required',
                'flf.sortOrder',
            ])
            ->innerJoin('{{%fieldlayoutfields}} flf', '[[flf.fieldId]] = [[fields.id]]')
            ->innerJoin('{{%fieldlayouttabs}} flt', '[[flt.id]] = [[flf.tabId]]')
            ->where(['flf.layoutId' => $layoutId])
            ->orderBy(['flt.sortOrder' => SORT_ASC, 'flf.sortOrder' => SORT_ASC])
            ->all();

        foreach ($results as $result) {
            $fields[] = $this->createField($result);
        }

        return $fields;
    }

    /**
     * Assembles a field layout from post data.
     *
     * @param string|null $namespace The namespace that the form data was posted in, if any
     * @return FieldLayout The field layout
     */
    public function assembleLayoutFromPost(string $namespace = null): FieldLayout
    {
        $paramPrefix = ($namespace ? rtrim($namespace, '.') . '.' : '');
        $request = Craft::$app->getRequest();

        $postedFieldLayout = $request->getBodyParam($paramPrefix . 'fieldLayout', []);
        $requiredFields = $request->getBodyParam($paramPrefix . 'requiredFields', []);

        $fieldLayout = $this->assembleLayout($postedFieldLayout, $requiredFields);
        $fieldLayout->id = $request->getBodyParam($paramPrefix . 'fieldLayoutId');

        return $fieldLayout;
    }

    /**
     * Assembles a field layout.
     *
     * @param array $postedFieldLayout The post data for the field layout
     * @param array $requiredFields The field IDs that should be marked as required in the field layout
     * @return FieldLayout The field layout
     */
    public function assembleLayout(array $postedFieldLayout, array $requiredFields = []): FieldLayout
    {
        $tabs = [];
        $fields = [];

        $tabSortOrder = 0;

        // Get all the fields
        $allFieldIds = [];

        foreach ($postedFieldLayout as $fieldIds) {
            foreach ($fieldIds as $fieldId) {
                $allFieldIds[] = $fieldId;
            }
        }

        if (!empty($allFieldIds)) {
            $allFieldsById = [];

            $results = $this->_createFieldQuery()
                ->where(['id' => $allFieldIds])
                ->all();

            foreach ($results as $result) {
                $allFieldsById[$result['id']] = $this->createField($result);
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
                $field->required = in_array($fieldId, $requiredFields, false);
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
     * @param FieldLayout $layout The field layout
     * @param bool $runValidation Whether the layout should be validated
     * @return bool Whether the field layout was saved successfully
     * @throws Exception if $layout->id is set to an invalid layout ID
     */
    public function saveLayout(FieldLayout $layout, bool $runValidation = true): bool
    {
        if (!$layout->id && $layout->uid) {
            // Maybe the ID just wasn't known
            $layout->id = Db::idByUid(Table::FIELDLAYOUTS, $layout->uid);
        }

        $isNewLayout = !$layout->id;

        // Make sure the tabs/fields are memoized on the layout
        foreach ($layout->getTabs() as $tab) {
            $tab->getFields();
        }

        // Fire a 'beforeSaveFieldLayout' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FIELD_LAYOUT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FIELD_LAYOUT, new FieldLayoutEvent([
                'layout' => $layout,
                'isNew' => $isNewLayout,
            ]));
        }

        if ($runValidation && !$layout->validate()) {
            Craft::info('Field layout not saved due to validation error.', __METHOD__);
            return false;
        }

        if (!$isNewLayout) {
            // Delete the old tabs/fields
            Craft::$app->getDb()->createCommand()
                ->delete(Table::FIELDLAYOUTTABS, ['layoutId' => $layout->id])
                ->execute();

            // Because in MySQL, you can't even rely on cascading deletes to work. ¯\_(ツ)_/¯
            Craft::$app->getDb()->createCommand()
                ->delete(Table::FIELDLAYOUTFIELDS, ['layoutId' => $layout->id])
                ->execute();

            // Get the current layout
            $layoutRecord = FieldLayoutRecord::findWithTrashed()
                ->andWhere(['id' => $layout->id])
                ->one();

            if (!$layoutRecord) {
                throw new Exception('Invalid field layout ID: ' . $layout->id);
            }
        } else {
            $layoutRecord = new FieldLayoutRecord();
        }

        // Save it
        $layoutRecord->type = $layout->type;

        // Use a pre-determined UID if available.
        if ($layout->uid) {
            $layoutRecord->uid = $layout->uid;
        }

        if (!$isNewLayout) {
            $layoutRecord->id = $layout->id;
            if (!$layout->uid) {
                $layoutRecord->uid = Db::uidById(Table::FIELDLAYOUTS, $layout->id);
            }
        }

        if ($layoutRecord->dateDeleted) {
            $layoutRecord->restore();
        } else {
            $layoutRecord->save(false);
        }

        if ($isNewLayout) {
            $layout->id = $layoutRecord->id;
        }

        $layout->uid = $layoutRecord->uid;

        foreach ($layout->getTabs() as $tab) {
            $tabRecord = new FieldLayoutTabRecord();
            $tabRecord->layoutId = $layout->id;
            $tabRecord->sortOrder = $tab->sortOrder;
            if (Craft::$app->getDb()->getIsMysql()) {
                $tabRecord->name = StringHelper::encodeMb4($tab->name);
            } else {
                $tabRecord->name = $tab->name;
            }
            $tabRecord->save(false);
            $tab->id = $tabRecord->id;
            $tab->uid = $tabRecord->uid;

            foreach ($tab->getFields() as $field) {
                /** @var Field $field */
                $fieldRecord = new FieldLayoutFieldRecord();
                $fieldRecord->layoutId = $layout->id;
                $fieldRecord->tabId = $tab->id;
                $fieldRecord->fieldId = $field->id;
                $fieldRecord->required = (bool)$field->required;
                $fieldRecord->sortOrder = $field->sortOrder;
                $fieldRecord->save(false);
            }
        }

        // Fire an 'afterSaveFieldLayout' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD_LAYOUT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_FIELD_LAYOUT, new FieldLayoutEvent([
                'layout' => $layout,
                'isNew' => $isNewLayout,
            ]));
        }

        return true;
    }

    /**
     * Deletes a field layout(s) by its ID.
     *
     * @param int|int[] $layoutId The field layout’s ID
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayoutById($layoutId): bool
    {
        if (!$layoutId) {
            return false;
        }

        foreach ((array)$layoutId as $thisLayoutId) {
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
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayout(FieldLayout $layout): bool
    {
        // Fire a 'beforeDeleteFieldLayout' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_FIELD_LAYOUT)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_FIELD_LAYOUT, new FieldLayoutEvent([
                'layout' => $layout
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->softDelete(Table::FIELDLAYOUTS, ['id' => $layout->id])
            ->execute();

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FIELD_LAYOUT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FIELD_LAYOUT, new FieldLayoutEvent([
                'layout' => $layout
            ]));
        }

        return true;
    }

    /**
     * Deletes field layouts associated with a given element type.
     *
     * @param string $type The element type
     * @return bool Whether the field layouts were deleted successfully
     */
    public function deleteLayoutsByType(string $type): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->softDelete(Table::FIELDLAYOUTS, ['type' => $type])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Restores a field layout by its ID.
     *
     * @param int $id The field layout’s ID
     * @return bool Whether the layout was restored successfully
     */
    public function restoreLayoutById(int $id): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->restore(Table::FIELDLAYOUTS, ['id' => $id])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Sets a new field version, so the ContentBehavior and ElementQueryBehavior classes
     * will get regenerated on the next request.
     */
    public function updateFieldVersion()
    {
        // Make sure that ContentBehavior and ElementQueryBehavior have already been loaded,
        // so the field version change won't be detected until the next request
        class_exists(ContentBehavior::class);
        class_exists(ElementQueryBehavior::class);

        $info = Craft::$app->getInfo();
        $info->fieldVersion = StringHelper::randomString(12);
        Craft::$app->saveInfo($info);
    }

    /**
     * Applies a field save to the database.
     *
     * @param string $fieldUid
     * @param array $data
     * @param string $context
     */
    public function applyFieldSave(string $fieldUid, array $data, string $context)
    {
        $groupUid = $data['fieldGroup'];

        // Ensure we have the field group in place first
        if ($groupUid) {
            Craft::$app->getProjectConfig()->processConfigChanges(self::CONFIG_FIELDGROUP_KEY . '.' . $groupUid);
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $fieldRecord = $this->_getFieldRecord($fieldUid);
            $groupRecord = $this->_getGroupRecord($groupUid);
            $isNewField = $fieldRecord->getIsNewRecord();
            $oldSettings = $fieldRecord->getOldAttribute('settings');

            /** @var Field $class */
            $class = $data['type'];

            // Create/alter the content table column
            $contentTable = Craft::$app->getContent()->contentTable;
            $oldColumnName = $this->oldFieldColumnPrefix . $fieldRecord->getOldHandle();
            $newColumnName = Craft::$app->getContent()->fieldColumnPrefix . $data['handle'];

            if ($class::hasContentColumn()) {
                $columnType = $data['contentColumnType'];

                // Clear the schema cache
                $db->getSchema()->refresh();

                // Are we dealing with an existing column?
                if ($db->columnExists($contentTable, $oldColumnName)) {
                    // Name change?
                    if ($oldColumnName !== $newColumnName) {
                        // Does the new column already exist?
                        if ($db->columnExists($contentTable, $newColumnName)) {
                            // Rename it so we don't lose any data
                            $db->createCommand()
                                ->renameColumn($contentTable, $newColumnName, $newColumnName . '_' . StringHelper::randomString(10))
                                ->execute();
                        }

                        // Rename the old column
                        $db->createCommand()
                            ->renameColumn($contentTable, $oldColumnName, $newColumnName)
                            ->execute();
                    }

                    // Alter it
                    $db->createCommand()
                        ->alterColumn($contentTable, $newColumnName, $columnType)
                        ->execute();
                } else {
                    // Does the new column already exist?
                    if ($db->columnExists($contentTable, $newColumnName)) {
                        // Rename it so we don't lose any data
                        $db->createCommand()
                            ->renameColumn($contentTable, $newColumnName, $newColumnName . '_' . StringHelper::randomString(10))
                            ->execute();
                    }

                    // Add the new column
                    $db->createCommand()
                        ->addColumn($contentTable, $newColumnName, $columnType)
                        ->execute();
                }
            } else {
                // Did the old field have a column we need to remove?
                if (
                    !$isNewField &&
                    $fieldRecord->getOldHandle() &&
                    $db->columnExists($contentTable, $oldColumnName)
                ) {
                    $db->createCommand()
                        ->dropColumn($contentTable, $oldColumnName)
                        ->execute();
                }
            }

            // Clear the translation key format if not using a custom translation method
            if ($data['translationMethod'] !== Field::TRANSLATION_METHOD_CUSTOM) {
                $data['translationKeyFormat'] = null;
            }

            $fieldRecord->uid = $fieldUid;
            $fieldRecord->groupId = $groupRecord->id;
            $fieldRecord->name = $data['name'];
            $fieldRecord->handle = $data['handle'];
            $fieldRecord->context = $context;
            $fieldRecord->instructions = $data['instructions'];
            $fieldRecord->searchable = $data['searchable'] ?? false;
            $fieldRecord->translationMethod = $data['translationMethod'];
            $fieldRecord->translationKeyFormat = $data['translationKeyFormat'];
            $fieldRecord->type = $data['type'];
            $fieldRecord->settings = $data['settings'] ?? null;

            $fieldRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Clear caches
        $this->refreshFields();

        // Update the field version
        $this->updateFieldVersion();

        // Tell the current ContentBehavior class about the field
        ContentBehavior::$fieldHandles[$fieldRecord->handle] = true;

        // For CP save requests, make sure we have all the custom data already saved on the object.
        /** @var Field $field */
        if (isset($this->_savingFields[$fieldUid])) {
            $field = $this->_savingFields[$fieldUid];

            if ($isNewField) {
                $field->id = $fieldRecord->id;
            }
        } else {
            $field = $this->getFieldById($fieldRecord->id);
        }

        if (!$isNewField) {
            // Save the old field handle and settings on the model in case the field type needs to do something with it.
            $field->oldHandle = $fieldRecord->getOldHandle();
            $field->oldSettings = is_string($oldSettings) ? Json::decode($oldSettings) : null;
        }

        $field->afterSave($isNewField);

        // Fire an 'afterSaveField' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD)) {
            $this->trigger(self::EVENT_AFTER_SAVE_FIELD, new FieldEvent([
                'field' => $field,
                'isNew' => $isNewField,
            ]));
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving groups.
     *
     * @return Query
     */
    private function _createGroupQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'uid',
            ])
            ->from([Table::FIELDGROUPS])
            ->orderBy(['name' => SORT_ASC]);
    }

    /**
     * Returns a Query object prepped for retrieving fields.
     *
     * @return Query
     */
    private function _createFieldQuery(): Query
    {
        $query = (new Query())
            ->select([
                'fields.id',
                'fields.dateCreated',
                'fields.dateUpdated',
                'fields.groupId',
                'fields.name',
                'fields.handle',
                'fields.context',
                'fields.instructions',
                'fields.translationMethod',
                'fields.translationKeyFormat',
                'fields.type',
                'fields.settings',
                'fields.uid'
            ])
            ->from(['{{%fields}} fields'])
            ->orderBy(['fields.name' => SORT_ASC, 'fields.handle' => SORT_ASC]);

        // todo: remove schema version condition after next beakpoint
        $schemaVersion = Craft::$app->getProjectConfig()->get('system.schemaVersion');
        if (version_compare($schemaVersion, '3.1.0', '>=')) {
            $query->addSelect(['fields.searchable']);
        }

        return $query;
    }

    /**
     * Returns a Query object prepped for retrieving layouts.
     *
     * @return Query
     */
    private function _createLayoutQuery(): Query
    {
        $query = (new Query)
            ->select([
                'id',
                'type',
                'uid'
            ])
            ->from([Table::FIELDLAYOUTS]);

        // todo: remove schema version condition after next beakpoint
        $schemaVersion = Craft::$app->getProjectConfig()->get('system.schemaVersion');
        if (version_compare($schemaVersion, '3.1.0', '>=')) {
            $query->where(['dateDeleted' => null]);
        }

        return $query;
    }

    /**
     * Returns a Query object prepped for retrieving layout tabs.
     *
     * @return Query
     */
    private function _createLayoutTabQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'layoutId',
                'name',
                'sortOrder',
                'uid'
            ])
            ->from([Table::FIELDLAYOUTTABS])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    /**
     * Gets a field group record or creates a new one.
     *
     * @param mixed $criteria ID or UID of the field group.
     * @return FieldGroupRecord
     */
    private function _getGroupRecord($criteria): FieldGroupRecord
    {
        if (is_numeric($criteria)) {
            $groupRecord = FieldGroupRecord::findOne($criteria);
        } else if (\is_string($criteria)) {
            $groupRecord = FieldGroupRecord::findOne(['uid' => $criteria]);
        }

        return $groupRecord ?? new FieldGroupRecord();
    }

    /**
     * Returns a field record for a given UID
     *
     * @param string $uid
     * @return FieldRecord
     * @throws FieldNotFoundException if $field->id is invalid
     */
    private function _getFieldRecord(string $uid): FieldRecord
    {
        return FieldRecord::findOne(['uid' => $uid]) ?? new FieldRecord();
    }
}
