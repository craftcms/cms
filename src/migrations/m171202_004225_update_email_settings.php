<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\mail\transportadapters\Sendmail;

/**
 * m171202_004225_update_email_settings migration.
 */
class m171202_004225_update_email_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->delete('{{%systemsettings}}', ['category' => 'mailer']);

        $settings = (new Query())
            ->select(['settings'])
            ->from(['{{%systemsettings}}'])
            ->where(['category' => 'email'])
            ->scalar();
        $settings = Json::decode($settings);

        if (isset($settings['transportType']) && $settings['transportType'] === 'craft\\mail\\transportadapters\\Php') {
            $settings['transportType'] = Sendmail::class;
            $this->update('{{%systemsettings}}', [
                'settings' => Json::encode($settings)
            ], ['category' => 'email'], [], false);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171202_004225_update_email_settings cannot be reverted.\n";
        return false;
    }
}
