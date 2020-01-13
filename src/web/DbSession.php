<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use yii\db\Query;

/**
 * Extends [[\yii\web\DbSession]] to to remove audit columns from the phpsessions table.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class DbSession extends \yii\web\DbSession
{
    public $sessionTable = '{{%phpsession}}';

    /**
     * @inheritDoc
     */
    public function regenerateID($deleteOldSession = false)
    {
        $oldID = session_id();

        // if no session is started, there is nothing to regenerate
        if (empty($oldID)) {
            return;
        }

        // Skip the immediate parent.
        \yii\web\Session::regenerateID(false);
        $newID = session_id();
        // if session id regeneration failed, no need to create/update it.
        if (empty($newID)) {
            Craft::warning('Failed to generate new session ID', __METHOD__);
            return;
        }

        $row = $this->db->useMaster(function() use ($oldID) {
            return (new Query())->from($this->sessionTable)
                ->where(['id' => $oldID])
                ->createCommand($this->db)
                ->queryOne();
        });

        if ($row !== false) {
            if ($deleteOldSession) {
                $this->db->createCommand()
                    ->update($this->sessionTable, ['id' => $newID], ['id' => $oldID])
                    ->execute();
            } else {
                $row['id'] = $newID;
                $this->db->createCommand()
                    ->insert($this->sessionTable, $row, false)
                    ->execute();
            }
        } else {
            // shouldn't reach here normally
            $this->db->createCommand()
                ->insert($this->sessionTable, $this->composeFields($newID, ''), false)
                ->execute();
        }
    }

    /**
     * @inheritDoc
     */
    public function writeSession($id, $data)
    {
        // exception must be caught in session write handler
        // https://secure.php.net/manual/en/function.session-set-save-handler.php#refsect1-function.session-set-save-handler-notes
        try {
            // ensure backwards compatability (fixed #9438)
            if ($this->writeCallback && !$this->fields) {
                $this->fields = $this->composeFields();
            }
            // ensure data consistency
            if (!isset($this->fields['data'])) {
                $this->fields['data'] = $data;
            } else {
                $_SESSION = $this->fields['data'];
            }
            // ensure 'id' and 'expire' are never affected by [[writeCallback]]
            $this->fields = array_merge($this->fields, [
                'id' => $id,
                'expire' => time() + $this->getTimeout(),
            ]);
            $this->fields = $this->typecastFields($this->fields);
            $this->db->createCommand()->upsert($this->sessionTable, $this->fields, false)->execute();
            $this->fields = [];
        } catch (\Exception $e) {
            Craft::$app->errorHandler->handleException($e);
            return false;
        }
        return true;
    }
}
