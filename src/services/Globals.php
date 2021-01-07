<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\db\Table;
use craft\elements\GlobalSet;
use craft\errors\ElementNotFoundException;
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
 * @since 3.0.0
 */
class Globals extends Component
{
    /**
     * @event GlobalSetEvent The event that is triggered before a global set is saved.
     */
    const EVENT_BEFORE_SAVE_GLOBAL_SET = 'beforeSaveGlobalSet';

    /**
     * @event GlobalSetEvent The event that is triggered after a global set is saved.
     */
    const EVENT_AFTER_SAVE_GLOBAL_SET = 'afterSaveGlobalSet';

    const CONFIG_GLOBALSETS_KEY = 'globalSets';

    /**
     * @var MemoizableArray[]|null
     * @see _allSets()
     */
    private $_allGlobalSets;

    /**
     * @var GlobalSet[][]|null
     * @see getEditableSets()
     */
    private $_editableGlobalSets;

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_allGlobalSets']);
        return $vars;
    }

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
        return ArrayHelper::getColumn($this->getAllSets(), 'id');
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
        return ArrayHelper::getColumn($this->getEditableSets(), 'id');
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
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->_allSets(Craft::$app->getSites()->getCurrentSite()->id)->all();
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
        /** @noinspection PhpUnhandledExceptionInspection */
        $currentSiteId = Craft::$app->getSites()->getCurrentSite()->id;

        if (!isset($this->_editableGlobalSets[$currentSiteId])) {
            $session = Craft::$app->getUser();
            $this->_editableGlobalSets[$currentSiteId] = ArrayHelper::where($this->_allSets($currentSiteId),
                function(GlobalSet $globalSet) use ($session): bool {
                    return $session->checkPermission("editGlobalSet:$globalSet->uid");
                }, true, true, false);
        }

        return $this->_editableGlobalSets[$currentSiteId];
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
        return count($this->getAllSets());
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
        return count($this->getEditableSets());
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
        /** @noinspection PhpUnhandledExceptionInspection */
        $currentSiteId = Craft::$app->getSites()->getCurrentSite()->id;

        if ($siteId === null) {
            $siteId = $currentSiteId;
        }

        if ($siteId == $currentSiteId) {
            return $this->_allSets($siteId)->firstWhere('id', $globalSetId);
        }

        return GlobalSet::find()
            ->siteId($siteId)
            ->id($globalSetId)
            ->one();
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
            return $this->_allSets($siteId)->firstWhere('handle', $globalSetHandle, true);
        }

        return GlobalSet::find()
            ->siteId($siteId)
            ->handle($globalSetHandle)
            ->one();
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
            $globalSet->uid = $globalSet->uid ?: StringHelper::UUID();
        } else if (!$globalSet->uid) {
            $globalSet->uid = Db::uidById(Table::GLOBALSETS, $globalSet->id);
        }

        $configPath = self::CONFIG_GLOBALSETS_KEY . '.' . $globalSet->uid;
        $configData = $globalSet->getConfig();
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save global set “{$globalSet->handle}”");

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
            $globalSetRecord = $this->_getGlobalSetRecord($globalSetUid, true);
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
            $element = null;
            $elementsService = Craft::$app->getElements();
            if (!$globalSetRecord->getIsNewRecord()) {
                $element = GlobalSet::find()
                    ->id($globalSetRecord->id)
                    ->trashed(null)
                    ->one();

                // If it's trashed, attempt to restore it, otherwise create a new element
                if ($element && $element->trashed) {
                    $element->fieldLayoutId = $globalSetRecord->fieldLayoutId;
                    if (
                        !$elementsService->saveElement($element) ||
                        !$elementsService->restoreElement($element)
                    ) {
                        $element = null;
                    }
                }
            }

            if (!$element) {
                $element = new GlobalSet();
            }

            $element->name = $globalSetRecord->name;
            $element->handle = $globalSetRecord->handle;
            $element->fieldLayoutId = $globalSetRecord->fieldLayoutId;

            if (!$elementsService->saveElement($element, false)) {
                throw new ElementNotFoundException('Unable to save the element required for global set.');
            }

            // Save the volume
            $globalSetRecord->id = $element->id;
            $globalSetRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_allGlobalSets = null;
        $this->_editableGlobalSets = null;

        // Fire an 'afterSaveGlobalSet' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GLOBAL_SET)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GLOBAL_SET, new GlobalSetEvent([
                'globalSet' => $this->getSetById($globalSetRecord->id),
                'isNew' => $isNewSet
            ]));
        }

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
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

        $this->deleteSet($globalSet);
        return true;
    }

    /**
     * Deletes a global set by its ID.
     *
     * @param GlobalSet $globalSet
     * @since 3.6.0
     */
    public function deleteSet(GlobalSet $globalSet): void
    {
        Craft::$app->getProjectConfig()->remove(self::CONFIG_GLOBALSETS_KEY . '.' . $globalSet->uid, "Delete the “{$globalSet->handle}” global set");
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

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Prune a deleted field from global set.
     *
     * @param FieldEvent $event
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $globalSets = $projectConfig->get(self::CONFIG_GLOBALSETS_KEY);

        // Engage stealth mode
        $projectConfig->muteEvents = true;

        // Loop through the global sets and prune the UID from field layouts.
        if (is_array($globalSets)) {
            foreach ($globalSets as $globalSetUid => $globalSet) {
                if (!empty($globalSet['fieldLayouts'])) {
                    foreach ($globalSet['fieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_GLOBALSETS_KEY . '.' . $globalSetUid . '.fieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid, 'Prune deleted field');
                            }
                        }
                    }
                }
            }
        }

        // Nuke all the layout fields from the DB
        Db::delete(Table::FIELDLAYOUTFIELDS, [
            'fieldId' => $field->id,
        ]);

        // Allow events again
        $projectConfig->muteEvents = false;
    }

    /**
     * Resets the memoized globals.
     *
     * @since 3.6.0
     */
    public function reset(): void
    {
        $this->_allGlobalSets = $this->_editableGlobalSets = null;
    }

    /**
     * Returns a memoizable array of all global sets for the given site.
     *
     * @param int $siteId
     * @return MemoizableArray
     */
    private function _allSets(int $siteId): MemoizableArray
    {
        if (!isset($this->_allGlobalSets[$siteId])) {
            $this->_allGlobalSets[$siteId] = new MemoizableArray(GlobalSet::find()->siteId($siteId)->all());
        }

        return $this->_allGlobalSets[$siteId];
    }

    /**
     * Gets a global set's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed sections in search
     * @return GlobalSetRecord
     */
    private function _getGlobalSetRecord(string $uid, bool $withTrashed = false): GlobalSetRecord
    {
        $query = $withTrashed ? GlobalSetRecord::findWithTrashed() : GlobalSetRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new GlobalSetRecord();
    }
}
