<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\errors\GlobalSetNotFoundException;
use craft\app\events\GlobalSetEvent;
use craft\app\elements\GlobalSet;
use craft\app\records\GlobalSet as GlobalSetRecord;
use yii\base\Component;

/**
 * Class Globals service.
 *
 * An instance of the Globals service is globally accessible in Craft via [[Application::globals `Craft::$app->getGlobals()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Globals extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event GlobalSetEvent The event that is triggered before a global set's content is saved.
     *
     * You may set [[GlobalSetEvent::isValid]] to `false` to prevent the global set's content from getting saved.
     */
    const EVENT_BEFORE_SAVE_GLOBAL_CONTENT = 'beforeSaveGlobalContent';

    /**
     * @event GlobalSetEvent The event that is triggered after a global set's content is saved.
     */
    const EVENT_AFTER_SAVE_GLOBAL_CONTENT = 'afterSaveGlobalContent';

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
     * @var
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
    public function getAllSetIds()
    {
        if (!isset($this->_allGlobalSetIds)) {
            $this->_allGlobalSetIds = (new Query())
                ->select('id')
                ->from('{{%globalsets}}')
                ->column();
        }

        return $this->_allGlobalSetIds;
    }

    /**
     * Returns all of the global set IDs that are editable by the current user.
     *
     * @return array
     */
    public function getEditableSetIds()
    {
        if (!isset($this->_editableGlobalSetIds)) {
            $this->_editableGlobalSetIds = [];
            $allGlobalSetIds = $this->getAllSetIds();

            foreach ($allGlobalSetIds as $globalSetId) {
                if (Craft::$app->getUser()->checkPermission('editGlobalSet:'.$globalSetId)) {
                    $this->_editableGlobalSetIds[] = $globalSetId;
                }
            }
        }

        return $this->_editableGlobalSetIds;
    }

    /**
     * Returns all global sets.
     *
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getAllSets($indexBy = null)
    {
        if (!isset($this->_allGlobalSets)) {
            $this->_allGlobalSets = GlobalSet::findAll();

            // Index them by ID
            foreach ($this->_allGlobalSets as $globalSet) {
                $this->_globalSetsById[$globalSet->id] = $globalSet;
            }
        }

        if (!$indexBy) {
            return $this->_allGlobalSets;
        }

        $globalSets = [];

        foreach ($this->_allGlobalSets as $globalSet) {
            $globalSets[$globalSet->$indexBy] = $globalSet;
        }

        return $globalSets;
    }

    /**
     * Returns all global sets that are editable by the current user.
     *
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getEditableSets($indexBy = null)
    {
        $globalSets = $this->getAllSets();
        $editableGlobalSetIds = $this->getEditableSetIds();
        $editableGlobalSets = [];

        foreach ($globalSets as $globalSet) {
            if (in_array($globalSet->id, $editableGlobalSetIds)) {
                if ($indexBy) {
                    $editableGlobalSets[$globalSet->$indexBy] = $globalSet;
                } else {
                    $editableGlobalSets[] = $globalSet;
                }
            }
        }

        return $editableGlobalSets;
    }

    /**
     * Returns the total number of global sets.
     *
     * @return integer
     */
    public function getTotalSets()
    {
        return count($this->getAllSetIds());
    }

    /**
     * Returns the total number of global sets that are editable by the current user.
     *
     * @return integer
     */
    public function getTotalEditableSets()
    {
        return count($this->getEditableSetIds());
    }

    /**
     * Returns a global set by its ID.
     *
     * @param integer     $globalSetId
     * @param string|null $localeId
     *
     * @return GlobalSet|null
     */
    public function getSetById($globalSetId, $localeId = null)
    {
        if (!$localeId) {
            $localeId = Craft::$app->language;
        }

        if ($localeId == Craft::$app->language) {
            if (!isset($this->_allGlobalSets)) {
                $this->getAllSets();
            }

            if (isset($this->_globalSetsById[$globalSetId])) {
                return $this->_globalSetsById[$globalSetId];
            }
        } else {
            return Craft::$app->getElements()->getElementById($globalSetId, GlobalSet::className(), $localeId);
        }

        return null;
    }

    /**
     * Returns a global set by its handle.
     *
     * @param integer     $globalSetHandle
     * @param string|null $localeId
     *
     * @return GlobalSet|null
     */
    public function getSetByHandle($globalSetHandle, $localeId = null)
    {
        if (!$localeId) {
            $localeId = Craft::$app->language;
        }

        if ($localeId == Craft::$app->language) {
            $globalSets = $this->getAllSets();

            foreach ($globalSets as $globalSet) {
                if ($globalSet->handle == $globalSetHandle) {
                    return $globalSet;
                }
            }
        } else {
            return GlobalSet::find()
                ->locale($localeId)
                ->handle($globalSetHandle)
                ->one();
        }

        return null;
    }

    /**
     * Saves a global set.
     *
     * @param GlobalSet $globalSet
     *
     * @return boolean
     * @throws GlobalSetNotFoundException if $globalSet->id is invalid
     * @throws \Exception if reasons
     */
    public function saveSet(GlobalSet $globalSet)
    {
        $isNewSet = !$globalSet->id;

        if (!$isNewSet) {
            $globalSetRecord = GlobalSetRecord::findOne($globalSet->id);

            if (!$globalSetRecord) {
                throw new GlobalSetNotFoundException("No global set exists with the ID '{$globalSet->id}'");
            }

            /** @var GlobalSet $oldSet */
            $oldSet = GlobalSet::create($globalSetRecord);
        } else {
            $globalSetRecord = new GlobalSetRecord();
        }

        $globalSetRecord->name = $globalSet->name;
        $globalSetRecord->handle = $globalSet->handle;

        $globalSetRecord->validate();
        $globalSet->addErrors($globalSetRecord->getErrors());

        if (!$globalSet->hasErrors()) {
            $transaction = Craft::$app->getDb()->beginTransaction();
            try {
                if (Craft::$app->getElements()->saveElement($globalSet, false)
                ) {
                    // Now that we have an element ID, save it on the other stuff
                    if ($isNewSet) {
                        $globalSetRecord->id = $globalSet->id;
                    }

                    // Is there a new field layout?
                    $fieldLayout = $globalSet->getFieldLayout();

                    if (!$fieldLayout->id) {
                        // Delete the old one
                        /** @noinspection PhpUndefinedVariableInspection */
                        if (!$isNewSet && $oldSet->fieldLayoutId) {
                            Craft::$app->getFields()->deleteLayoutById($oldSet->fieldLayoutId);
                        }

                        // Save the new one
                        Craft::$app->getFields()->saveLayout($fieldLayout);

                        // Update the global set record/model with the new layout ID
                        $globalSet->fieldLayoutId = $fieldLayout->id;
                        $globalSetRecord->fieldLayoutId = $fieldLayout->id;
                    }

                    $globalSetRecord->save(false);

                    $transaction->commit();

                    return true;
                }
            } catch (\Exception $e) {
                $transaction->rollBack();

                throw $e;
            }
        }

        return false;
    }

    /**
     * Deletes a global set by its ID.
     *
     * @param integer $setId
     *
     * @return boolean Whether the global set was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteSetById($setId)
    {
        if (!$setId) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select('fieldLayoutId')
                ->from('{{%globalsets}}')
                ->where(['id' => $setId])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            $affectedRows = Craft::$app->getElements()->deleteElementById($setId);

            $transaction->commit();

            return (bool)$affectedRows;
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Saves a global set's content
     *
     * @param GlobalSet $globalSet
     *
     * @return boolean Whether the global set’s content was saved successfully
     * @throws \Exception if reasons
     */
    public function saveContent(GlobalSet $globalSet)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeSaveGlobalContent' event
            $event = new GlobalSetEvent([
                'globalSet' => $globalSet
            ]);

            $this->trigger(static::EVENT_BEFORE_SAVE_GLOBAL_CONTENT, $event);

            // Is the event giving us the go-ahead?
            if ($event->isValid) {
                $success = Craft::$app->getElements()->saveElement($globalSet);

                // If it didn't work, rollback the transaction in case something changed in onBeforeSaveGlobalContent
                if (!$success) {
                    $transaction->rollBack();

                    return false;
                }
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we saved the user, in case something changed
            // in onBeforeSaveGlobalContent
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterSaveGlobalContent' event
            $this->trigger(static::EVENT_AFTER_SAVE_GLOBAL_CONTENT,
                new GlobalSetEvent([
                    'globalSet' => $globalSet
                ]));
        }

        return $success;
    }
}
