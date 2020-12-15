<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\Localization;
use craft\helpers\MigrationHelper;
use yii\base\InvalidArgumentException;

/**
 * m150428_231346_userpreferences migration.
 */
class m150428_231346_userpreferences extends Migration
{
    private $_usersTable;
    private $_prefsTable;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_usersTable = $this->db->getSchema()->getRawTableName(Table::USERS);
        $this->_prefsTable = $this->db->getSchema()->getRawTableName(Table::USERPREFERENCES);

        // In case this was run in a previous update attempt
        $this->dropTableIfExists($this->_prefsTable);

        $this->_createUserPrefsTable();
        $this->_createUserPrefsIndexAndForeignKey();
        $this->_populateUserPrefsTable();

        MigrationHelper::dropForeignKeyIfExists($this->_usersTable, ['preferredLocale'], $this);
        $this->dropColumn($this->_usersTable, 'preferredLocale');
        $this->dropColumn($this->_usersTable, 'weekStartDay');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150428_231346_userpreferences cannot be reverted.\n";

        return false;
    }

    /**
     * Creates the userpreferences table
     */
    private function _createUserPrefsTable()
    {
        $this->createTable($this->_prefsTable, [
            'userId' => $this->integer()->notNull(),
            'preferences' => $this->text(),
            'PRIMARY KEY([[userId]])',
        ]);
    }

    /**
     * Creates an index and foreign key on the `userId` column on the userpreferences table.
     */
    private function _createUserPrefsIndexAndForeignKey()
    {
        $this->createIndex(null, $this->_prefsTable, 'userId', true);
        $this->addForeignKey(null, $this->_prefsTable, 'userId', $this->_usersTable, 'id', 'CASCADE');
    }

    /**
     * Populates the userpreferences table.
     */
    private function _populateUserPrefsTable()
    {
        $users = (new Query())
            ->select(['id', 'preferredLocale', 'weekStartDay'])
            ->from([$this->_usersTable])
            ->where([
                'not',
                [
                    'preferredLocale' => null,
                    'weekStartDay' => '0'
                ]
            ])
            ->all($this->db);

        if (!empty($users)) {
            $rows = [];

            foreach ($users as $user) {
                $prefs = [];

                if (!empty($user['preferredLocale'])) {
                    try {
                        $prefs['language'] = Localization::normalizeLanguage($user['preferredLocale']);
                    } catch (InvalidArgumentException $e) {
                        // Do nothing.
                    }
                }

                if ($user['weekStartDay'] != 0) {
                    $prefs['weekStartDay'] = $user['weekStartDay'];
                }

                $rows[] = [$user['id'], Json::encode($prefs)];
            }

            $this->batchInsert($this->_prefsTable, [
                'userId',
                'preferences'
            ], $rows, false);
        }
    }
}
