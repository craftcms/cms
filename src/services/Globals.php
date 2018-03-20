<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\elements\GlobalSet;
use craft\errors\GlobalSetNotFoundException;
use craft\events\GlobalSetEvent;
use craft\helpers\ArrayHelper;
use craft\records\GlobalSet as GlobalSetRecord;
use yii\base\Component;
use yii\base\Exception;

/**
 * Globals service.
 * An instance of the Globals service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getGlobals()|<code>Craft::$app->globals</code>]].
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
     * @return array
     */
    public function getAllSetIds(): array
    {
        if ($this->_allGlobalSetIds !== null) {
            return $this->_allGlobalSetIds;
        }

        return $this->_allGlobalSetIds = (new Query())
            ->select(['id'])
            ->from(['{{%globalsets}}'])
            ->column();
    }

    /**
     * Returns all of the global set IDs that are editable by the current user.
     *
     * @return array
     */
    public function getEditableSetIds(): array
    {
        if ($this->_editableGlobalSetIds !== null) {
            return $this->_editableGlobalSetIds;
        }

        $this->_editableGlobalSetIds = [];
        $allGlobalSetIds = $this->getAllSetIds();

        foreach ($allGlobalSetIds as $globalSetId) {
            if (Craft::$app->getUser()->checkPermission('editGlobalSet:'.$globalSetId)) {
                $this->_editableGlobalSetIds[] = $globalSetId;
            }
        }

        return $this->_editableGlobalSetIds;
    }

    /**
     * Returns all global sets.
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
     * @return int
     */
    public function getTotalSets(): int
    {
        return count($this->getAllSetIds());
    }

    /**
     * Returns the total number of global sets that are editable by the current user.
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

        if (!$isNewSet) {
            $globalSetRecord = GlobalSetRecord::findOne($globalSet->id);

            if (!$globalSetRecord) {
                throw new GlobalSetNotFoundException("No global set exists with the ID '{$globalSet->id}'");
            }
        } else {
            $globalSetRecord = new GlobalSetRecord();
        }

        $globalSetRecord->name = $globalSet->name;
        $globalSetRecord->handle = $globalSet->handle;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Save the field layout
            $fieldLayout = $globalSet->getFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $globalSet->fieldLayoutId = $fieldLayout->id;
            $globalSetRecord->fieldLayoutId = $fieldLayout->id;

            // Save the global set
            if (!Craft::$app->getElements()->saveElement($globalSet, false)) {
                throw new Exception('Couldn’t save the global set.');
            }

            // Now that we have an element ID, save the record
            if ($isNewSet) {
                $globalSetRecord->id = $globalSet->id;
            }

            $globalSetRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveGlobalSet' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GLOBAL_SET)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GLOBAL_SET, new GlobalSetEvent([
                'globalSet' => $globalSet,
                'isNew' => $isNewSet
            ]));
        }

        return true;
    }
}
