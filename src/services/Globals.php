<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Field;
use craft\db\Query;
use craft\db\Table;
use craft\elements\GlobalSet;
use craft\errors\GlobalSetNotFoundException;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\events\GlobalSetEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\records\GlobalSet as GlobalSetRecord;
use yii\base\Component;

/**
 * Globals service.
 * An instance of the Globals service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getGlobals()|`Craft::$app->globals`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Globals extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event GlobalSetEvent The event that is triggered before a global set is saved.
     */
    const EVENT_BEFORE_SAVE_GLOBAL_SET = 'beforeSaveGlobalSet';

    /**
     * @event GlobalSetEvent The event that is triggered after a global set is saved.
     */
    const EVENT_AFTER_SAVE_GLOBAL_SET = 'afterSaveGlobalSet';

    const CONFIG_GLOBALSETS_KEY = 'globalSets';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_allGlobalSetIds;

    /**
     * @var
     */
    private $_editableGlobalSetIds;

    /**
     * @var GlobalSet[]|null
     */
    private $_allGlobalSets;

    /**
     * @var
     */
    private $_globalSetsById;

    // Public Methods
    // =========================================================================

    /**
     * Returns all of the global set IDs.
     *
     * ---
     *
     * ```php
     * $globalSetIds = Craft::$app->globals->allSetIds;
     * ```
     * ```twig
     * {% set globalSetIds = craft.app.globals.allSetIds %}
     * ```
     *
     * @return array
     */
    public function getAllSetIds(): array
    {
        if ($this->_allGlobalSetIds !== null) {
            return $this->_allGlobalSetIds;
        }

        return $this->_allGlobalSetIds = (new Query())
            ->select(['id'])
            ->from([Table::GLOBALSETS])
            ->column();
    }

    /**
     * Returns all of the global set IDs that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $globalSetIds = Craft::$app->globals->editableSetIds;
     * ```
     * ```twig
     * {% set globalSetIds = craft.app.globals.editableSetIds %}
     * ```
     *
     * @return array
     */
    public function getEditableSetIds(): array
    {
        if ($this->_editableGlobalSetIds !== null) {
            return $this->_editableGlobalSetIds;
        }

        $this->_editableGlobalSetIds = [];
        $allGlobalSets = $this->getAllSets();

        foreach ($allGlobalSets as $globalSet) {
            if (Craft::$app->getUser()->checkPermission('editGlobalSet:' . $globalSet->uid)) {
                $this->_editableGlobalSetIds[] = $globalSet->id;
            }
        }

        return $this->_editableGlobalSetIds;
    }

    /**
     * Returns all global sets.
     *
     * ---
     *
     * ```php
     * $globalSets = Craft::$app->globals->allSets;
     * ```
     * ```twig
     * {% set globalSets = craft.app.globals.allSets %}
     * ```
     *
     * @return GlobalSet[]
     */
    public function getAllSets(): array
    {
        if ($this->_allGlobalSets !== null) {
            return $this->_allGlobalSets;
        }

        $this->_allGlobalSets = GlobalSet::findAll();
        $this->_globalSetsById = ArrayHelper::index($this->_allGlobalSets, 'id');

        return $this->_allGlobalSets;
    }

    /**
     * Returns all global sets that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $globalSets = Craft::$app->globals->editableSets;
     * ```
     * ```twig
     * {% set globalSets = craft.app.globals.editableSets %}
     * ```
     *
     * @return GlobalSet[]
     */
    public function getEditableSets(): array
    {
        $globalSets = $this->getAllSets();
        $editableGlobalSetIds = $this->getEditableSetIds();
        $editableGlobalSets = [];

        foreach ($globalSets as $globalSet) {
            if (in_array($globalSet->id, $editableGlobalSetIds, false)) {
                $editableGlobalSets[] = $globalSet;
            }
        }

        return $editableGlobalSets;
    }

    /**
     * Returns the total number of global sets.
     *
     * ---
     *
     * ```php
     * $total = Craft::$app->globals->totalSets;
     * ```
     * ```twig
     * {% set total = craft.app.globals.totalSets %}
     * ```
     *
     * @return int
     */
    public function getTotalSets(): int
    {
        return count($this->getAllSetIds());
    }

    /**
     * Returns the total number of global sets that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $total = Craft::$app->globals->totalEditableSets;
     * ```
     * ```twig
     * {% set total = craft.app.globals.totalEditableSets %}
     * ```
     *
     * @return int
     */
    public function getTotalEditableSets(): int
    {
        return count($this->getEditableSetIds());
    }

    /**
     * Returns a global set by its ID.
     *
     * ---
     *
     * ```php
     * $globalSet = Craft::$app->globals->getSetById(1);
     * ```
     * ```twig
     * {% set globalSet = craft.app.globals.getSetById(1) %}
     * ```
     *
     * @param int $globalSetId
     * @param int|null $siteId
     * @return GlobalSet|null
     */
    public function getSetById(int $globalSetId, int $siteId = null)
    {
        if ($siteId === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        if ($siteId == Craft::$app->getSites()->getCurrentSite()->id) {
            if ($this->_allGlobalSets === null) {
                $this->getAllSets();
            }

            if (!isset($this->_globalSetsById[$globalSetId])) {
                return null;
            }

            return $this->_globalSetsById[$globalSetId];
        }

        /** @var GlobalSet|null $globalSet */
        $globalSet = Craft::$app->getElements()->getElementById($globalSetId, GlobalSet::class, $siteId);

        return $globalSet;
    }

    /**
     * Returns a global set by its handle.
     *
     * ---
     *
     * ```php
     * $globalSet = Craft::$app->globals->getSetByHandle('footerInfo');
     * ```
     * ```twig
     * {% set globalSet = craft.app.globals.getSetByHandle('footerInfo') %}
     * ```
     *
     * @param string $globalSetHandle
     * @param int|null $siteId
     * @return GlobalSet|null
     */
    public function getSetByHandle(string $globalSetHandle, int $siteId = null)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $currentSiteId = Craft::$app->getSites()->getCurrentSite()->id;

        if ($siteId === null) {
            $siteId = $currentSiteId;
        }

        if ($siteId == $currentSiteId) {
            $globalSets = $this->getAllSets();

            foreach ($globalSets as $globalSet) {
                if ($globalSet->handle == $globalSetHandle) {
                    return $globalSet;
                }
            }
        } else {
            return GlobalSet::find()
                ->siteId($siteId)
                ->handle($globalSetHandle)
                ->one();
        }

        return null;
    }

    /**
     * Saves a global set.
     *
     * @param GlobalSet $globalSet The global set to be saved
     * @param bool $runValidation Whether the global set should be validated
     * @return bool
     * @throws GlobalSetNotFoundException if $globalSet->id is invalid
     * @throws \Throwable if reasons
     */
    public function saveSet(GlobalSet $globalSet, bool $runValidation = true): bool
    {
        $isNewSet = !$globalSet->id;

        // Fire a 'beforeSaveGlobalSet' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_GLOBAL_SET)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_GLOBAL_SET, new GlobalSetEvent([
                'globalSet' => $globalSet,
                'isNew' => $isNewSet,
            ]));
        }

        // Don't validate required custom fields
        if ($runValidation && !$globalSet->validate()) {
            Craft::info('Global set not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewSet) {
            $globalSet->uid = StringHelper::UUID();
        } else if (!$globalSet->uid) {
            $globalSet->uid = Db::uidById(Table::GLOBALSETS, $globalSet->id);
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $configData = [
            'name' => $globalSet->name,
            'handle' => $globalSet->handle,
        ];

        $fieldLayout = $globalSet->getFieldLayout();
        $fieldLayoutConfig = $fieldLayout->getConfig();

        if ($fieldLayoutConfig) {
            if (empty($fieldLayout->id)) {
                $layoutUid = StringHelper::UUID();
                $fieldLayout->uid = $layoutUid;
            } else {
                $layoutUid = Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id);
            }

            $configData['fieldLayouts'] = [
                $layoutUid => $fieldLayoutConfig
            ];
        }

        $configPath = self::CONFIG_GLOBALSETS_KEY . '.' . $globalSet->uid;
        $projectConfig->set($configPath, $configData);

        if ($isNewSet) {
            $globalSet->id = Db::idByUid(Table::GLOBALSETS, $globalSet->uid);
        }

        return true;
    }

    /**
     * Handle global set change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedGlobalSet(ConfigEvent $event)
    {
        $globalSetUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $globalSetRecord = $this->_getGlobalSetRecord($globalSetUid);
            $isNewSet = $globalSetRecord->getIsNewRecord();

            $globalSetRecord->name = $data['name'];
            $globalSetRecord->handle = $data['handle'];
            $globalSetRecord->uid = $globalSetUid;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $globalSetRecord->fieldLayoutId;
                $layout->type = GlobalSet::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $globalSetRecord->fieldLayoutId = $layout->id;
            } else if ($globalSetRecord->fieldLayoutId) {
                // Delete the field layout
                Craft::$app->getFields()->deleteLayoutById($globalSetRecord->fieldLayoutId);
                $globalSetRecord->fieldLayoutId = null;
            }

            // Make sure there's an element for it.
            $setId = Db::idByUid(Table::GLOBALSETS, $globalSetUid);

            $elementsService = Craft::$app->getElements();

            if (!$setId) {
                $element = new GlobalSet();
            } else {
                $element = GlobalSet::find()
                    ->id($setId)
                    ->trashed(null)
                    ->one();

                // If it's trashed, attempt to restore it, otherwise create a new element
                if ($element->trashed) {
                    $element->fieldLayoutId = $globalSetRecord->fieldLayoutId;
                    if (
                        !$elementsService->saveElement($element) ||
                        !$elementsService->restoreElement($element)
                    ) {
                        $element = new GlobalSet();
                    }
                }
            }

            $element->name = $globalSetRecord->name;
            $element->handle = $globalSetRecord->handle;
            $element->fieldLayoutId = $globalSetRecord->fieldLayoutId;
            $elementsService->saveElement($element, false);

            // Save the volume
            $globalSetRecord->id = $element->id;
            $globalSetRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_allGlobalSetIds = null;
        $this->_editableGlobalSetIds = null;
        $this->_allGlobalSets = null;
        unset($this->_globalSetsById[$globalSetRecord->id]);

        // Fire an 'afterSaveGlobalSet' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GLOBAL_SET)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GLOBAL_SET, new GlobalSetEvent([
                'globalSet' => $this->getSetById($globalSetRecord->id),
                'isNew' => $isNewSet
            ]));
        }
    }

    /**
     * Deletes a global set by its ID.
     *
     * @param int $globalSetId
     * @return bool Whether the global set was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteGlobalSetById(int $globalSetId): bool
    {
        if (!$globalSetId) {
            return false;
        }

        $globalSet = $this->getSetById($globalSetId);

        if (!$globalSet) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_GLOBALSETS_KEY . '.' . $globalSet->uid);
        return true;
    }

    /**
     * Handle global set getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedGlobalSet(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $globalSetRecord = $this->_getGlobalSetRecord($uid);

        if (!$globalSetRecord->id) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from([Table::GLOBALSETS])
                ->where(['id' => $globalSetRecord->id])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            Craft::$app->getElements()->deleteElementById($globalSetRecord->id);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Prune a deleted field from global set.
     *
     * @param FieldEvent $event
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        /** @var Field $field */
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $globalSets = $projectConfig->get(self::CONFIG_GLOBALSETS_KEY);

        // Loop through the global sets and prune the UID from field layouts.
        if (is_array($globalSets)) {
            foreach ($globalSets as $globalSetUid => $globalSet) {
                if (!empty($globalSet['fieldLayouts'])) {
                    foreach ($globalSet['fieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_GLOBALSETS_KEY . '.' . $globalSetUid . '.fieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid);
                            }
                        }
                    }
                }
            }
        }
    }

    // Private methods
    // =========================================================================

    /**
     * Gets a global set's record by uid.
     *
     * @param string $uid
     * @return GlobalSetRecord
     */
    private function _getGlobalSetRecord(string $uid): GlobalSetRecord
    {
        return GlobalSetRecord::findOne(['uid' => $uid]) ?? new GlobalSetRecord();
    }

}
