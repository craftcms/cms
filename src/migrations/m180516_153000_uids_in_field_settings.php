<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

/**
 * m180516_153000_uids_in_field_settings migration.
 */
class m180516_153000_uids_in_field_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $fields = (new Query())
            ->select(['id', 'settings', 'type'])
            ->from(['{{%fields}}'])
            ->all();

        $folderIds = [];
        $sectionIds = [];
        $siteIds = [];
        $tagGroupIds = [];
        $categoryGroupIds = [];
        $userGroupIds = [];

        foreach ($fields as $field) {
            $settings = Json::decodeIfJson($field['settings']);

            if (!empty($settings['targetSiteId'])) {
                $siteIds[] = $settings['targetSiteId'];
            }

            switch ($field['type']){
                case 'craft\fields\Assets':
                    list(, $folderIds[]) = explode(':', $settings['defaultUploadLocationSource']);
                    list(, $folderIds[]) = explode(':', $settings['singleUploadLocationSource']);

                    if (is_array($settings['sources'])) {
                        foreach ($settings['sources'] as $source) {
                            list(, $folderIds[]) = explode(':', $source);
                        }
                    }

                    break;
                case 'craft\fields\Entries':
                    if (is_array($settings['sources'])) {
                        foreach ($settings['sources'] as $source) {
                            list(, $sectionIds[]) = explode(':', $source);
                        }
                    }

                    break;
                case 'craft\fields\Users':
                    if (is_array($settings['sources'])) {
                        foreach ($settings['sources'] as $source) {
                            if (strpos($source, ':') !== false) {
                                list(, $userGroupIds[]) = explode(':', $source);
                            }
                        }
                    }

                    break;
                case 'craft\fields\Categories':
                    list(, $categoryGroupIds[]) = explode(':', $settings['source']);

                    break;
                case 'craft\fields\Tags':
                    list(, $tagGroupIds[]) = explode(':', $settings['source']);

                    break;
            }


        }

        $folders = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%volumefolders}}'])
            ->where(['id' => $folderIds])
            ->pairs();

        $sections = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%sections}}'])
            ->where(['id' => $sectionIds])
            ->pairs();

        $userGroups = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%usergroups}}'])
            ->where(['id' => $userGroupIds])
            ->pairs();

        $sites = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%sites}}'])
            ->where(['id' => $siteIds])
            ->pairs();

        $tagGroups = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%taggroups}}'])
            ->where(['id' => $tagGroupIds])
            ->pairs();

        $categoryGroups = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%categorygroups}}'])
            ->where(['id' => $categoryGroupIds])
            ->pairs();

        foreach ($fields as $field) {
            $settings = Json::decodeIfJson($field['settings']);

            if (array_key_exists('targetSiteId', $settings)) {
                $settings['targetSiteId'] =   $sites[$settings['targetSiteId']] ?? null;
            }

            switch ($field['type']){
                case 'craft\fields\Assets':
                    $default = explode(':', $settings['defaultUploadLocationSource']);
                    $single = explode(':', $settings['singleUploadLocationSource']);

                    $settings['defaultUploadLocationSource'] = $default[0].':'.$folders[$default[1]] ??  null;
                    $settings['singleUploadLocationSource'] = $single[0].':'.$folders[$single[1]] ??  null;

                    if (is_array($settings['sources'])) {
                        $newSources = [];

                        foreach ($settings['sources'] as $source) {
                            $source = explode(':', $source);
                            $newSources[] = $source[0].':'.$folders[$source[1]] ??  null;
                        }

                        $settings['sources'] = $newSources;
                    }

                    break;
                case 'craft\fields\Entries':
                    if (is_array($settings['sources'])) {
                        $newSources = [];

                        foreach ($settings['sources'] as $source) {
                            $source = explode(':', $source);
                            $newSources[] = $source[0].':'.$sections[$source[1]] ??  null;
                        }

                        $settings['sources'] = $newSources;
                    }

                    break;
                case 'craft\fields\Users':
                    if (is_array($settings['sources'])) {
                        $newSources = [];

                        foreach ($settings['sources'] as $source) {
                            $source = explode(':', $source);

                            if (count($source) > 1) {
                                $newSources[] = $source[0].':'.$userGroups[$source[1]] ??  null;
                            } else {
                                $newSources[] = $source[0];
                            }
                        }

                        $settings['sources'] = $newSources;
                    }

                    break;
                case 'craft\fields\Categories':
                    $source = explode(':', $settings['source']);
                    $settings['source'] = $source[0].':'.$categoryGroups[$source[1]] ?? null;

                    break;
                case 'craft\fields\Tags':
                    $source = explode(':', $settings['source']);
                    $settings['source'] = $source[0].':'.$tagGroups[$source[1]] ?? null;

                    break;
            }

            $settings = Json::encode($settings);

            $this->update('{{%fields}}', ['settings' => $settings], ['id' => $field['id']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180516_153000_uids_in_field_settings cannot be reverted.\n";
        return false;
    }
}
