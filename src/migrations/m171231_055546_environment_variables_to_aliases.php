<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\volumes\Local;

/**
 * m171231_055546_environment_variables_to_aliases migration.
 */
class m171231_055546_environment_variables_to_aliases extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Parse the site URLs
        $sites = (new Query())
            ->select(['id', 'baseUrl'])
            ->from([Table::SITES])
            ->where(['like', 'baseUrl', '{%', false])
            ->all();

        foreach ($sites as $site) {
            if ($this->_parseEnvString($site['baseUrl'])) {
                $this->update(Table::SITES, [
                    'baseUrl' => $site['baseUrl']
                ], ['id' => $site['id']], [], false);
            }
        }

        // Parse the 'path' and 'url' local volume settings
        $localVolumes = (new Query())
            ->select(['id', 'settings'])
            ->from([Table::VOLUMES])
            ->where(['type' => Local::class])
            ->all();

        foreach ($localVolumes as $volume) {
            $changed = false;
            $settings = Json::decodeIfJson($volume['settings']);

            foreach (['path', 'url'] as $setting) {
                if (isset($settings[$setting]) && $this->_parseEnvString($settings[$setting])) {
                    $changed = true;
                }
            }

            if ($changed) {
                $this->update(Table::VOLUMES, [
                    'settings' => Json::encode($settings)
                ], ['id' => $volume['id']], [], false);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171231_055546_environment_variables_to_aliases cannot be reverted.\n";
        return false;
    }

    /**
     * Checks if the string follows the format "{someTag}subpath" (subpath optional),
     * and if so, converts the tag to a Yii alias ("@someTag/subpath").
     *
     * @param string &$str The string to parse
     * @return bool Whether the string was converted
     */
    private function _parseEnvString(string &$str): bool
    {
        if (preg_match('/^\s*\{(\w+)\}([^\{\}]*)$/', $str, $matches)) {
            $str = '@' . $matches[1] . ($matches[2] ? '/' . trim(str_replace('\\', '/', $matches[2]), '/') : '');
            return true;
        }

        // Also check if it should start with "@storage"
        $normalizedStr = str_replace('\\', '/', $str);
        $normalizedStorage = str_replace('\\', '/', Craft::$app->getPath()->getStoragePath());

        if (StringHelper::startsWith($normalizedStr . '/', $normalizedStorage . '/')) {
            $str = '@storage' . substr($normalizedStr, strlen($normalizedStorage));
            return true;
        }

        return false;
    }
}
