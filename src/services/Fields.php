<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\FieldLayoutElement;
use craft\base\MemoizableArray;
use craft\behaviors\CustomFieldBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\events\FieldGroupEvent;
use craft\events\FieldLayoutEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets as AssetsField;
use craft\fields\Categories as CategoriesField;
use craft\fields\Checkboxes;
use craft\fields\Color;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Entries as EntriesField;
use craft\fields\Lightswitch;
use craft\fields\Matrix as MatrixField;
use craft\fields\MissingField;
use craft\fields\Money;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Table as TableField;
use craft\fields\Tags as TagsField;
use craft\fields\Time;
use craft\fields\Url;
use craft\fields\Users as UsersField;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldGroup;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\records\Field as FieldRecord;
use craft\records\FieldGroup as FieldGroupRecord;
use craft\records\FieldLayout as FieldLayoutRecord;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;

/**
 * Fields service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getFields()|`Craft::$app->fields`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Fields extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering field types.
     *
     * Field types must implement [[FieldInterface]]. [[Field]] provides a base implementation.
     *
     * See [Field Types](https://craftcms.com/docs/4.x/extend/field-types.html) for documentation on creating field types.
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
    public const EVENT_REGISTER_FIELD_TYPES = 'registerFieldTypes';

    /**
     * @event FieldGroupEvent The event that is triggered before a field group is saved.
     */
    public const EVENT_BEFORE_SAVE_FIELD_GROUP = 'beforeSaveFieldGroup';

    /**
     * @event FieldGroupEvent The event that is triggered after a field group is saved.
     */
    public const EVENT_AFTER_SAVE_FIELD_GROUP = 'afterSaveFieldGroup';

    /**
     * @event FieldGroupEvent The event that is triggered before a field group delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event FieldGroupEvent The event that is triggered before a field group is deleted.
     */
    public const EVENT_BEFORE_DELETE_FIELD_GROUP = 'beforeDeleteFieldGroup';

    /**
     * @event FieldGroupEvent The event that is triggered after a field group is deleted.
     */
    public const EVENT_AFTER_DELETE_FIELD_GROUP = 'afterDeleteFieldGroup';

    /**
     * @event FieldEvent The event that is triggered before a field is saved.
     */
    public const EVENT_BEFORE_SAVE_FIELD = 'beforeSaveField';

    /**
     * @event FieldEvent The event that is triggered after a field is saved.
     */
    public const EVENT_AFTER_SAVE_FIELD = 'afterSaveField';

    /**
     * @event FieldEvent The event that is triggered before a field is deleted.
     */
    public const EVENT_BEFORE_DELETE_FIELD = 'beforeDeleteField';

    /**
     * @event FieldEvent The event that is triggered before a field delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_FIELD_DELETE = 'beforeApplyFieldDelete';

    /**
     * @event FieldEvent The event that is triggered after a field is deleted.
     */
    public const EVENT_AFTER_DELETE_FIELD = 'afterDeleteField';

    /**
     * @event FieldLayoutEvent The event that is triggered before a field layout is saved.
     */
    public const EVENT_BEFORE_SAVE_FIELD_LAYOUT = 'beforeSaveFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered after a field layout is saved.
     */
    public const EVENT_AFTER_SAVE_FIELD_LAYOUT = 'afterSaveFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered before a field layout is deleted.
     */
    public const EVENT_BEFORE_DELETE_FIELD_LAYOUT = 'beforeDeleteFieldLayout';

    /**
     * @event FieldLayoutEvent The event that is triggered after a field layout is deleted.
     */
    public const EVENT_AFTER_DELETE_FIELD_LAYOUT = 'afterDeleteFieldLayout';

    /**
     * @var string The active field context
     * @since 5.0.0
     */
    public string $fieldContext = 'global';

    /**
     * @var MemoizableArray<FieldGroup>|null
     * @see _groups()
     */
    private ?MemoizableArray $_groups = null;

    /**
     * @var MemoizableArray<FieldInterface>|null
     * @see _fields()
     */
    private ?MemoizableArray $_fields = null;

    /**
     * @var MemoizableArray<FieldLayout>|null
     * @see _layouts()
     */
    private ?MemoizableArray $_layouts = null;

    /**
     * @var array
     */
    private array $_savingFields = [];

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_groups'], $vars['_fields']);
        return $vars;
    }

    // Groups
    // -------------------------------------------------------------------------

    /**
     * Returns a memoizable array of all field groups.
     *
     * @return MemoizableArray<FieldGroup>
     */
    private function _groups(): MemoizableArray
    {
        if (!isset($this->_groups)) {
            $groups = [];
            foreach ($this->_createGroupQuery()->all() as $result) {
                $groups[] = new FieldGroup($result);
            }
            $this->_groups = new MemoizableArray($groups);
        }

        return $this->_groups;
    }

    /**
     * Returns all field groups.
     *
     * @return FieldGroup[] The field groups
     */
    public function getAllGroups(): array
    {
        return $this->_groups()->all();
    }

    /**
     * Returns a field group by its ID.
     *
     * @param int $groupId The field group’s ID
     * @return FieldGroup|null The field group, or null if it doesn’t exist
     */
    public function getGroupById(int $groupId): ?FieldGroup
    {
        return $this->_groups()->firstWhere('id', $groupId);
    }

    /**
     * Returns a field group by its UID.
     *
     * @param string $groupUid The field group’s UID
     * @return FieldGroup|null The field group, or null if it doesn’t exist
     * @since 3.3.0
     */
    public function getGroupByUid(string $groupUid): ?FieldGroup
    {
        return $this->_groups()->firstWhere('uid', $groupUid, true);
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

        if ($isNewGroup) {
            $group->uid = StringHelper::UUID();
        }

        $configPath = ProjectConfig::PATH_FIELD_GROUPS . '.' . $group->uid;
        $configData = $group->getConfig();
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save field group “{$group->name}”");

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
    public function handleChangedGroup(ConfigEvent $event): void
    {
        $data = $event->newValue;
        $uid = $event->tokenMatches[0];

        $groupRecord = $this->_getGroupRecord($uid, true);
        $isNewGroup = $groupRecord->getIsNewRecord();

        // If this is a new group, set the UID we want.
        if ($isNewGroup) {
            $groupRecord->uid = $uid;
        }

        $groupRecord->name = $data['name'];

        if ($groupRecord->dateDeleted) {
            $groupRecord->restore();
        } else {
            $groupRecord->save(false);
        }

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
    public function handleDeletedGroup(ConfigEvent $event): void
    {
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

        Craft::$app->getDb()->createCommand()
            ->softDelete(Table::FIELDGROUPS, ['id' => $groupRecord->id])
            ->execute();

        // Update caches
        $this->_groups = null;

        // Fire an 'afterDeleteFieldGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FIELD_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FIELD_GROUP, new FieldGroupEvent([
                'group' => $group,
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
        /** @var FieldGroupRecord|null $groupRecord */
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
                'group' => $group,
            ]));
        }

        // Manually delete the fields (rather than relying on cascade deletes) so we have a chance to delete the
        // content columns
        $fields = $this->getFieldsByGroupId($group->id);

        foreach ($fields as $field) {
            $this->deleteField($field);
        }

        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_FIELD_GROUPS . '.' . $group->uid, "Delete the “{$group->name}” field group");
        return true;
    }

    // Fields
    // -------------------------------------------------------------------------

    /**
     * Returns all available field type classes.
     *
     * @return string[] The available field type classes
     * @phpstan-return class-string<FieldInterface>[]
     */
    public function getAllFieldTypes(): array
    {
        $fieldTypes = [
            AssetsField::class,
            CategoriesField::class,
            Checkboxes::class,
            Color::class,
            Date::class,
            Dropdown::class,
            Email::class,
            EntriesField::class,
            Lightswitch::class,
            MatrixField::class,
            Money::class,
            MultiSelect::class,
            Number::class,
            PlainText::class,
            RadioButtons::class,
            TableField::class,
            TagsField::class,
            Time::class,
            Url::class,
            UsersField::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $fieldTypes,
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
        return ArrayHelper::where(
            $this->getAllFieldTypes(),
            fn(string $class) => /** @var string|FieldInterface $class */ $class::dbType() !== null,
            keepKeys: false,
        );
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
        // If the field has any validation errors and has an ID, swap it with the saved field
        if (!$field->getIsNew() && $field->hasErrors()) {
            $field = $this->getFieldById($field->id);
        }

        $dbType = $field::dbType();

        if (!is_string($dbType)) {
            return $includeCurrent ? [get_class($field)] : [];
        }

        $types = [];

        foreach ($this->getAllFieldTypes() as $class) {
            /** @var string|FieldInterface $class */
            /** @phpstan-var class-string<FieldInterface>|FieldInterface $class */
            if ($class === get_class($field)) {
                if ($includeCurrent) {
                    $types[] = $class;
                }
                continue;
            }

            $otherDbType = $class::dbType();

            if (is_string($otherDbType) && Db::areColumnTypesCompatible($dbType, $otherDbType)) {
                $types[] = $class;
            }
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
     * @template T of FieldInterface
     * @param string|array $config The field’s class name, or its config, with a `type` value and optionally a `settings` value
     * @phpstan-param class-string<T>|array{type:class-string<T>,id?:int|string,uid?:string} $config
     * @return T The field
     */
    public function createField(mixed $config): FieldInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (!empty($config['id']) && empty($config['uid']) && is_numeric($config['id'])) {
            $uid = Db::uidById(Table::FIELDS, $config['id']);
            $config['uid'] = $uid;
        }

        try {
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
     * Returns a memoizable array of all fields.
     *
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     *
     * @return MemoizableArray<FieldInterface>
     */
    private function _fields(mixed $context = null): MemoizableArray
    {
        if (!isset($this->_fields)) {
            $fields = [];
            foreach ($this->_createFieldQuery()->all() as $result) {
                $fields[] = $this->createField($result);
            }
            $this->_fields = new MemoizableArray($fields);
        }

        if ($context === false) {
            return $this->_fields;
        }

        if ($context === null) {
            $context = $this->fieldContext;
        }

        if (is_array($context)) {
            return $this->_fields->whereIn('context', $context, true);
        }

        return $this->_fields->where('context', $context, true);
    }

    /**
     * Returns all fields within a field context(s).
     *
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     * @return FieldInterface[] The fields
     */
    public function getAllFields(mixed $context = null): array
    {
        return $this->_fields($context)->all();
    }

    /**
     * Returns all fields that store content in the `elements_sites.content` table.
     *
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     * @return FieldInterface[] The fields
     */
    public function getFieldsWithContent(mixed $context = null): array
    {
        return ArrayHelper::where(
            $this->getAllFields($context),
            fn(FieldInterface $field) => $field::dbType() !== null,
            keepKeys: false,
        );
    }

    /**
     * Returns all fields that don’t store content in the `elements_sites.content` table.
     *
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     * @return FieldInterface[] The fields
     * @since 4.3.2
     */
    public function getFieldsWithoutContent(mixed $context = null): array
    {
        return ArrayHelper::where(
            $this->getAllFields($context),
            fn(FieldInterface $field) => $field::dbType() === null,
            keepKeys: false,
        );
    }

    /**
     * Returns all fields of a certain type.
     *
     * @param string $type The field type
     * @phpstan-param class-string<FieldInterface> $type
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     * @return FieldInterface[] The fields
     * @since 4.4.0
     */
    public function getFieldsByType(string $type, mixed $context = null): array
    {
        return ArrayHelper::where(
            $this->getAllFields($context),
            fn(FieldInterface $field) => $field instanceof $type,
            keepKeys: false
        );
    }

    /**
     * Returns a field by its ID.
     *
     * @param int $fieldId The field’s ID
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldById(int $fieldId): ?FieldInterface
    {
        return $this->_fields(false)->firstWhere('id', $fieldId);
    }

    /**
     * Returns a field by its UID.
     *
     * @param string $fieldUid The field’s UID
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByUid(string $fieldUid): ?FieldInterface
    {
        return $this->_fields(false)->firstWhere('uid', $fieldUid, true);
    }

    /**
     * Returns a field by its handle and optional context.
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
     * @param string|string[]|false|null $context The field context(s) to fetch fields from. Defaults to [[\craft\services\Fields::$fieldContext]].
     * Set to `false` to get all fields regardless of context.
     * @return FieldInterface|null The field, or null if it doesn’t exist
     */
    public function getFieldByHandle(string $handle, mixed $context = null): ?FieldInterface
    {
        return $this->_fields($context)->firstWhere('handle', $handle, true);
    }

    /**
     * Returns whether a field exists with a given handle and context.
     *
     * @param string $handle The field handle
     * @param string|null $context The field context (defauts to [[\craft\services\Fields::$fieldContext]])
     * @return bool Whether a field with that handle exists
     */
    public function doesFieldWithHandleExist(string $handle, ?string $context = null): bool
    {
        return ArrayHelper::contains($this->getAllFields($context), 'handle', $handle, true);
    }

    /**
     * Returns all the fields in a given group.
     *
     * @param int $groupId The field group’s ID
     * @return FieldInterface[] The fields
     */
    public function getFieldsByGroupId(int $groupId): array
    {
        return $this->_fields(false)->where('groupId', $groupId)->all();
    }

    /**
     * Returns the config for the given field.
     *
     * @param FieldInterface $field
     * @return array
     * @since 3.1.0
     */
    public function createFieldConfig(FieldInterface $field): array
    {
        $config = [
            'name' => $field->name,
            'handle' => $field->handle,
            'columnSuffix' => $field->columnSuffix,
            'instructions' => $field->instructions,
            'searchable' => $field->searchable,
            'translationMethod' => $field->translationMethod,
            'translationKeyFormat' => $field->translationKeyFormat,
            'type' => get_class($field),
            'settings' => ProjectConfigHelper::packAssociativeArrays($field->getSettings()),
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
     * @throws Throwable if reasons
     */
    public function saveField(FieldInterface $field, bool $runValidation = true): bool
    {
        if ($field instanceof MissingField) {
            $error = $field->errorMessage ?? "Unable to find component class '$field->expectedType'.";
            $field->addError('type', $error);
            return false;
        }

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
        $appliedConfig = false;

        // Only store field data in the project config for global context
        if ($field->context === 'global') {
            $configPath = ProjectConfig::PATH_FIELDS . '.' . $field->uid;
            $appliedConfig = Craft::$app->getProjectConfig()->set($configPath, $configData, "Save field “{$field->handle}”");
        }

        if (!$appliedConfig) {
            // If it’s not a global field, or there weren't any changes in the main field settings, apply the save to the DB + call afterSave()
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
     * @since 3.1.2
     */
    public function prepFieldForSave(FieldInterface $field): void
    {
        // Clear the translation key format if not using a custom translation method
        if ($field->translationMethod !== Field::TRANSLATION_METHOD_CUSTOM) {
            $field->translationKeyFormat = null;
        }

        $isNew = $field->getIsNew();

        // Make sure it's got a UUID
        if ($isNew) {
            if (empty($field->uid)) {
                $field->uid = StringHelper::UUID();
            }
        } elseif (!$field->uid) {
            $field->uid = Db::uidById(Table::FIELDS, $field->id);
        }

        // Store with all the populated data for future reference.
        $this->_savingFields[$field->uid] = $field;
    }

    /**
     * Handle field changes.
     *
     * @param ConfigEvent $event
     * @throws Throwable
     */
    public function handleChangedField(ConfigEvent $event): void
    {
        $data = $event->newValue;
        $fieldUid = $event->tokenMatches[0];

        if (!is_array($data)) {
            return;
        }

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
     * @throws Throwable if reasons
     */
    public function deleteField(FieldInterface $field): bool
    {
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
            Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_FIELDS . '.' . $field->uid, "Delete the “{$field->handle}” field");
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
    public function handleDeletedField(ConfigEvent $event): void
    {
        $fieldUid = $event->tokenMatches[0];
        $this->applyFieldDelete($fieldUid);
    }

    /**
     * Applies a field delete to the database.
     *
     * @param string $fieldUid
     * @throws Throwable if database error
     * @since 3.1.0
     */
    public function applyFieldDelete(string $fieldUid): void
    {
        $fieldRecord = $this->_getFieldRecord($fieldUid);

        if (!$fieldRecord->id) {
            return;
        }

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

            // Delete the row in fields
            Db::delete(Table::FIELDS, [
                'id' => $fieldRecord->id,
            ]);

            $field->afterDelete();

            $transaction->commit();
        } catch (Throwable $e) {
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

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Refreshes the internal field cache.
     *
     * This should be called whenever a field is updated or deleted directly in
     * the database, rather than going through this service.
     *
     * @since 3.0.20
     */
    public function refreshFields(): void
    {
        $this->_fields = null;
        $this->updateFieldVersion();
    }

    // Layouts
    // -------------------------------------------------------------------------

    /**
     * Returns a memoizable array of all field layouts.
     *
     * @return MemoizableArray<FieldLayout>
     */
    private function _layouts(): MemoizableArray
    {
        if (!isset($this->_layouts)) {
            $layouts = [];
            if (Craft::$app->getIsInstalled()) {
                foreach ($this->_createLayoutQuery()->all() as $result) {
                    if (array_key_exists('config', $result)) {
                        $config = ArrayHelper::remove($result, 'config');
                        if ($config) {
                            $result += Json::decode($config);
                        }
                        $loadTabs = false;
                    } else {
                        $loadTabs = true;
                    }

                    $layouts[] = $layout = $this->createLayout($result);

                    // todo: remove after the next breakpoint
                    if ($loadTabs) {
                        $this->_legacyTabsByLayoutId($layout);
                    }
                }
            }
            $this->_layouts = new MemoizableArray($layouts);
        }

        return $this->_layouts;
    }

    private function _legacyTabsByLayoutId(FieldLayout $layout): void
    {
        $tabQuery = (new Query())
            ->select([
                'id',
                'layoutId',
                'name',
                'elements',
                'sortOrder',
                'uid',
            ])
            ->from('{{%fieldlayouttabs}}')
            ->where(['layoutId' => $layout->id])
            ->orderBy(['sortOrder' => SORT_ASC]);

        if (Craft::$app->getDb()->columnExists('{{%fieldlayouttabs}}', 'settings')) {
            $tabQuery->addSelect('settings');
        }

        $tabResults = $tabQuery->all();
        $isMysql = Craft::$app->getDb()->getIsMysql();
        $tabs = [];

        foreach ($tabResults as $tabResult) {
            if ($isMysql) {
                $tabResult['name'] = html_entity_decode($tabResult['name'], ENT_QUOTES | ENT_HTML5);
            }

            if (array_key_exists('settings', $tabResult)) {
                $settings = ArrayHelper::remove($tabResult, 'settings');
                if ($settings) {
                    $tabResult += Json::decode($settings);
                }
            }

            $elements = ArrayHelper::remove($tabResult, 'elements');
            if ($elements) {
                $elements = Json::decode($elements);
            } else {
                // old school
                $elements = [];

                $fieldResults = (new Query())
                    ->select(['fieldId', 'required'])
                    ->from(['{{%fieldlayoutfields}}'])
                    ->where(['tabId' => $tabResult['id']])
                    ->orderBy(['sortOrder' => SORT_ASC])
                    ->all();

                foreach ($fieldResults as $fieldResult) {
                    $field = $this->getFieldById($fieldResult['fieldId']);
                    if ($field) {
                        $elements[] = new CustomField($field, [
                            'uid' => StringHelper::UUID(),
                            'required' => $fieldResult['required'],
                        ]);
                    }
                }
            }

            // Set the layout before anything else
            $tabResult = ['layout' => $layout] + $tabResult;
            $tabResult['elements'] = $elements;

            $tabs[] = new FieldLayoutTab($tabResult);
        }

        $layout->setTabs($tabs);
    }

    /**
     * Returns all saved field layouts.
     *
     * @return FieldLayout[]
     * @since 5.0.0
     */
    public function getAllLayouts(): array
    {
        return $this->_layouts()->all();
    }

    /**
     * Returns a field layout by its ID.
     *
     * @param int $layoutId The field layout’s ID
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutById(int $layoutId): ?FieldLayout
    {
        return $this->_layouts()->firstWhere('id', $layoutId);
    }

    /**
     * Returns a field layout by its UUID.
     *
     * @param string $uid The field layout’s UUID
     * @return FieldLayout|null The field layout, or null if it doesn’t exist
     */
    public function getLayoutByUid(string $uid): ?FieldLayout
    {
        return $this->_layouts()->firstWhere('uid', $uid);
    }

    /**
     * Returns field layouts by their IDs.
     *
     * @param int[] $layoutIds The field layouts’ IDs
     * @return FieldLayout[] The field layouts
     * @since 3.7.27
     */
    public function getLayoutsByIds(array $layoutIds): array
    {
        return $this->_layouts()->whereIn('id', $layoutIds)->all();
    }

    /**
     * Returns a field layout by its associated element type.
     *
     * @param string $type The associated element type
     * @phpstan-param class-string<ElementInterface> $type
     * @return FieldLayout The field layout
     */
    public function getLayoutByType(string $type): FieldLayout
    {
        return $this->_layouts()->firstWhere('type', $type)
            ?? new FieldLayout(['type' => $type]);
    }

    /**
     * Returns all of the field layouts associated with a given element type.
     *
     * @param string $type
     * @phpstan-param class-string<ElementInterface> $type
     * @return FieldLayout[] The field layouts
     * @since 3.5.0
     */
    public function getLayoutsByType(string $type): array
    {
        return $this->_layouts()->where('type', $type)->all();
    }

    /**
     * Creates a field layout from the given config.
     *
     * @param array $config
     * @return FieldLayout
     * @since 4.0.0
     */
    public function createLayout(array $config): FieldLayout
    {
        $config['class'] = FieldLayout::class;
        return Craft::createObject($config);
    }

    /**
     * Creates a field layout element instance from its config.
     *
     * @template T of FieldLayoutElement
     * @param array $config
     * @phpstan-param array{type:class-string<T>} $config
     * @return T
     * @throws InvalidArgumentException if `$config['type']` does not implement [[FieldLayoutElement]]
     * @since 3.5.0
     */
    public function createLayoutElement(array $config): FieldLayoutElement
    {
        $type = ArrayHelper::remove($config, 'type');

        if (!$type || !is_subclass_of($type, FieldLayoutElement::class)) {
            throw new InvalidArgumentException("Invalid field layout element class: $type");
        }

        $config['class'] = $type;
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::createObject($config);
    }

    /**
     * Assembles a field layout from post data.
     *
     * @param string|null $namespace The namespace that the form data was posted in, if any
     * @return FieldLayout The field layout
     * @throws BadRequestHttpException
     */
    public function assembleLayoutFromPost(?string $namespace = null): FieldLayout
    {
        $paramPrefix = $namespace ? rtrim($namespace, '.') . '.' : '';
        $config = Json::decode(Craft::$app->getRequest()->getBodyParam($paramPrefix . 'fieldLayout'));
        return $this->createLayout($config);
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
        if (!$layout->id) {
            // Maybe the ID just wasn't known
            $layout->id = Db::idByUid(Table::FIELDLAYOUTS, $layout->uid);
        }

        $isNewLayout = !$layout->id;

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
            // Get the current layout
            /** @var FieldLayoutRecord|null $layoutRecord */
            $layoutRecord = FieldLayoutRecord::findWithTrashed()
                ->andWhere(['id' => $layout->id])
                ->one();

            if (!$layoutRecord) {
                throw new Exception('Invalid field layout ID: ' . $layout->id);
            }
        } else {
            $layoutRecord = new FieldLayoutRecord();
        }

        // Save the layout
        $layoutRecord->type = $layout->type;
        $layoutRecord->config = $layout->getConfig();
        $layoutRecord->uid = $layout->uid;

        if (!$isNewLayout) {
            $layoutRecord->id = $layout->id;
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

        // Fire an 'afterSaveFieldLayout' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FIELD_LAYOUT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_FIELD_LAYOUT, new FieldLayoutEvent([
                'layout' => $layout,
                'isNew' => $isNewLayout,
            ]));
        }

        // Clear caches
        $this->_layouts = null;

        // Refresh CustomFieldBehavior in case any custom field handles were just added/removed
        $this->updateFieldVersion();

        return true;
    }

    /**
     * Deletes a field layout(s) by its ID.
     *
     * @param int|int[] $layoutId The field layout’s ID
     * @return bool Whether the field layout was deleted successfully
     */
    public function deleteLayoutById(array|int $layoutId): bool
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
                'layout' => $layout,
            ]));
        }

        Craft::$app->getDb()->createCommand()
            ->softDelete(Table::FIELDLAYOUTS, ['id' => $layout->id])
            ->execute();

        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FIELD_LAYOUT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FIELD_LAYOUT, new FieldLayoutEvent([
                'layout' => $layout,
            ]));
        }

        // Clear caches
        $this->_layouts = null;

        return true;
    }

    /**
     * Deletes field layouts associated with a given element type.
     *
     * @param string $type The element type
     * @phpstan-param class-string<ElementInterface> $type
     * @return bool Whether the field layouts were deleted successfully
     */
    public function deleteLayoutsByType(string $type): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->softDelete(Table::FIELDLAYOUTS, ['type' => $type])
            ->execute();

        // Clear caches
        $this->_layouts = null;

        return (bool)$affectedRows;
    }

    /**
     * Restores a field layout by its ID.
     *
     * @param int $id The field layout’s ID
     * @return bool Whether the layout was restored successfully
     * @since 3.1.0
     */
    public function restoreLayoutById(int $id): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->restore(Table::FIELDLAYOUTS, ['id' => $id])
            ->execute();

        // Clear caches
        $this->_layouts = null;

        return (bool)$affectedRows;
    }

    /**
     * Returns the current field version.
     *
     * @return string|null
     * @since 3.7.21
     */
    public function getFieldVersion(): ?string
    {
        $fieldVersion = Craft::$app->getInfo()->fieldVersion;

        // If it doesn't start with `3@`, then it needs to be updated
        if ($fieldVersion === null || !str_starts_with($fieldVersion, '3@')) {
            return null;
        }

        return $fieldVersion;
    }

    /**
     * Sets a new field version, so the CustomFieldBehavior class
     * will get regenerated on the next request.
     *
     */
    public function updateFieldVersion(): void
    {
        // Make sure that CustomFieldBehavior has already been loaded,
        // so the field version change won't be detected until the next request
        class_exists(CustomFieldBehavior::class);

        $info = Craft::$app->getInfo();
        $info->fieldVersion = '3@' . StringHelper::randomString(10);
        Craft::$app->saveInfo($info, ['fieldVersion']);
    }

    /**
     * Applies a field save to the database.
     *
     * @param string $fieldUid
     * @param array $data
     * @param string $context
     * @since 3.1.0
     */
    public function applyFieldSave(string $fieldUid, array $data, string $context): void
    {
        $groupUid = $data['fieldGroup'];

        // Ensure we have the field group in the place first
        if ($groupUid) {
            Craft::$app->getProjectConfig()->processConfigChanges(ProjectConfig::PATH_FIELD_GROUPS . '.' . $groupUid);
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $fieldRecord = $this->_getFieldRecord($fieldUid);
            $groupRecord = $groupUid ? $this->_getGroupRecord($groupUid) : null;
            $isNewField = $fieldRecord->getIsNewRecord();
            $oldSettings = $fieldRecord->getOldAttribute('settings');

            // Track whether we should remove the field’s search indexes after save
            $searchable = $data['searchable'] ?? false;
            $deleteSearchIndexes = !$isNewField && !$searchable && $fieldRecord->searchable;

            // Clear the translation key format if not using a custom translation method
            if ($data['translationMethod'] !== Field::TRANSLATION_METHOD_CUSTOM) {
                $data['translationKeyFormat'] = null;
            }

            if (!empty($data['settings']) && is_array($data['settings'])) {
                $data['settings'] = ProjectConfigHelper::unpackAssociativeArrays($data['settings']);
            }

            $fieldRecord->uid = $fieldUid;
            $fieldRecord->groupId = $groupRecord->id ?? null;
            $fieldRecord->name = $data['name'];
            $fieldRecord->handle = $data['handle'];
            $fieldRecord->context = $context;
            $fieldRecord->columnSuffix = $data['columnSuffix'] ?? null;
            $fieldRecord->instructions = $data['instructions'];
            $fieldRecord->searchable = $searchable;
            $fieldRecord->translationMethod = $data['translationMethod'];
            $fieldRecord->translationKeyFormat = $data['translationKeyFormat'];
            $fieldRecord->type = $data['type'];
            $fieldRecord->settings = $data['settings'] ?? null;

            $fieldRecord->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->refreshFields();

        // Tell the current CustomFieldBehavior class about the field
        CustomFieldBehavior::$fieldHandles[$fieldRecord->handle] = true;

        // For control panel save requests, make sure we have all the custom data already saved on the object.
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

        // If we just dropped `searchable`, delete the field’s search indexes immediately.
        if ($deleteSearchIndexes) {
            Db::delete(Table::SEARCHINDEX, [
                'attribute' => 'field',
                'fieldId' => $field->id,
            ]);
        }

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

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
            ->where(['dateDeleted' => null])
            ->orderBy(['name' => SORT_ASC]);
    }

    /**
     * Returns a Query object prepped for retrieving fields.
     *
     * @return Query
     */
    private function _createFieldQuery(): Query
    {
        return (new Query())
            ->select([
                'fields.id',
                'fields.dateCreated',
                'fields.dateUpdated',
                'fields.groupId',
                'fields.name',
                'fields.handle',
                'fields.context',
                'fields.columnSuffix',
                'fields.instructions',
                'fields.searchable',
                'fields.translationMethod',
                'fields.translationKeyFormat',
                'fields.type',
                'fields.settings',
                'fields.uid',
            ])
            ->from(['fields' => Table::FIELDS])
            ->orderBy(['fields.name' => SORT_ASC, 'fields.handle' => SORT_ASC]);
    }

    /**
     * Returns a Query object prepped for retrieving layouts.
     *
     * @return Query
     */
    private function _createLayoutQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'type',
                'uid',
            ])
            ->from([Table::FIELDLAYOUTS])
            ->where(['dateDeleted' => null]);

        // todo: remove after the next breakpoint
        if (Craft::$app->getDb()->columnExists(Table::FIELDLAYOUTS, 'config')) {
            $query->addSelect('config');
        }

        return $query;
    }

    /**
     * Gets a field group record or creates a new one.
     *
     * @param int|string $criteria ID or UID of the field group.
     * @param bool $withTrashed Whether to include trashed field groups in search
     * @return FieldGroupRecord
     */
    private function _getGroupRecord(int|string $criteria, bool $withTrashed = false): FieldGroupRecord
    {
        $query = $withTrashed ? FieldGroupRecord::findWithTrashed() : FieldGroupRecord::find();

        if (is_numeric($criteria)) {
            $query->where(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->where(['uid' => $criteria]);
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @var FieldGroupRecord */
        return $query->one() ?? new FieldGroupRecord();
    }

    /**
     * Returns a field record for a given UID
     *
     * @param string $uid
     * @return FieldRecord
     */
    private function _getFieldRecord(string $uid): FieldRecord
    {
        return FieldRecord::findOne(['uid' => $uid]) ?? new FieldRecord();
    }
}
