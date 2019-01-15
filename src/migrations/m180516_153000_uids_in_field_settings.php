<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
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
            ->from([Table::FIELDS])
            ->all();

        $folderIds = [];
        $sectionIds = [];
        $siteIds = [];
        $tagGroupIds = [];
        $categoryGroupIds = [];
        $userGroupIds = [];

        foreach ($fields as $field) {
            if ($field['settings']) {
                $settings = Json::decodeIfJson($field['settings']) ?: [];
            } else {
                $settings = [];
            }

            if (!empty($settings['targetSiteId'])) {
                $siteIds[] = $settings['targetSiteId'];
            }

            switch ($field['type']) {
                case 'craft\fields\Assets':
                    list(, $folderIds[]) = explode(':', $settings['defaultUploadLocationSource']);
                    list(, $folderIds[]) = explode(':', $settings['singleUploadLocationSource']);

                    if (is_array($settings['sources'])) {
                        foreach ($settings['sources'] as $source) {
                            if (strpos($source, ':') !== false) {
                                list(, $folderIds[]) = explode(':', $source);
                            }
                        }
                    }

                    break;
                case 'craft\fields\Entries':
                    if (is_array($settings['sources'])) {
                        foreach ($settings['sources'] as $source) {
                            if (strpos($source, ':') !== false) {
                                list(, $sectionIds[]) = explode(':', $source);
                            }
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
            ->from([Table::VOLUMEFOLDERS])
            ->where(['id' => $folderIds])
            ->pairs();

        $sections = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::SECTIONS])
            ->where(['id' => $sectionIds])
            ->pairs();

        $userGroups = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::USERGROUPS])
            ->where(['id' => $userGroupIds])
            ->pairs();

        $sites = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::SITES])
            ->where(['id' => $siteIds])
            ->pairs();

        $tagGroups = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::TAGGROUPS])
            ->where(['id' => $tagGroupIds])
            ->pairs();

        $categoryGroups = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::CATEGORYGROUPS])
            ->where(['id' => $categoryGroupIds])
            ->pairs();

        foreach ($fields as $field) {
            if ($field['settings']) {
                $settings = Json::decodeIfJson($field['settings']) ?: [];
            } else {
                $settings = [];
            }

            if (array_key_exists('targetSiteId', $settings)) {
                $settings['targetSiteId'] = $sites[$settings['targetSiteId']] ?? null;
            }

            switch ($field['type']) {
                case 'craft\fields\Assets':
                    $default = explode(':', $settings['defaultUploadLocationSource']);
                    $single = explode(':', $settings['singleUploadLocationSource']);

                    $settings['defaultUploadLocationSource'] = isset($folders[$default[1]]) ? $default[0] . ':' . $folders[$default[1]] : null;
                    $settings['singleUploadLocationSource'] = isset($folders[$single[1]]) ? $single[0] . ':' . $folders[$single[1]] : null;

                    if (is_array($settings['sources'])) {
                        $newSources = [];

                        foreach ($settings['sources'] as $source) {
                            $source = explode(':', $source);
                            if (count($source) > 1) {
                                $newSources[] = $source[0] . ':' . ($folders[$source[1]] ?? $source[1]);
                            } else {
                                $newSources[] = $source[0];
                            }
                        }

                        $settings['sources'] = $newSources;
                    }

                    break;
                case 'craft\fields\Entries':
                    if (is_array($settings['sources'])) {
                        $newSources = [];

                        foreach ($settings['sources'] as $source) {
                            $source = explode(':', $source);
                            if (count($source) > 1) {
                                $newSources[] = $source[0] . ':' . ($sections[$source[1]] ?? $source[1]);
                            } else {
                                $newSources[] = $source[0];
                            }
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
                                $newSources[] = $source[0] . ':' . ($userGroups[$source[1]] ?? $source[1]);
                            } else {
                                $newSources[] = $source[0];
                            }
                        }

                        $settings['sources'] = $newSources;
                    }

                    break;
                case 'craft\fields\Categories':
                    $source = explode(':', $settings['source']);
                    $settings['source'] = $source[0] . ':' . ($categoryGroups[$source[1]] ?? $source[1]);

                    break;
                case 'craft\fields\Tags':
                    $source = explode(':', $settings['source']);
                    $settings['source'] = $source[0] . ':' . ($tagGroups[$source[1]] ?? $source[1]);

                    break;
            }

            $settings = Json::encode($settings);

            $this->update(Table::FIELDS, ['settings' => $settings], ['id' => $field['id']], [], false);
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
