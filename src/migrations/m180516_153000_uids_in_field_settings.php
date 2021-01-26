<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Assets;
use craft\fields\Categories;
use craft\fields\Entries;
use craft\fields\Tags;
use craft\fields\Users;
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
                case Assets::class:
                    if (!empty($settings['defaultUploadLocationSource']) && strpos($settings['defaultUploadLocationSource'], ':') !== false) {
                        [, $folderIds[]] = explode(':', $settings['defaultUploadLocationSource']);
                    }
                    if (!empty($settings['singleUploadLocationSource']) && strpos($settings['singleUploadLocationSource'], ':') !== false) {
                        [, $folderIds[]] = explode(':', $settings['singleUploadLocationSource']);
                    }

                    if (!empty($settings['sources']) && is_array($settings['sources'])) {
                        foreach ($settings['sources'] as $source) {
                            if (strpos($source, ':') !== false) {
                                [, $folderIds[]] = explode(':', $source);
                            }
                        }
                    }

                    break;
                case Entries::class:
                    if (!empty($settings['sources']) && is_array($settings['sources'])) {
                        foreach ($settings['sources'] as $source) {
                            if (strpos($source, ':') !== false) {
                                [, $sectionIds[]] = explode(':', $source);
                            }
                        }
                    }

                    break;
                case Users::class:
                    if (!empty($settings['sources']) && is_array($settings['sources'])) {
                        foreach ($settings['sources'] as $source) {
                            if (strpos($source, ':') !== false) {
                                [, $userGroupIds[]] = explode(':', $source);
                            }
                        }
                    }

                    break;
                case Categories::class:
                    if (!empty($settings['source']) && strpos($settings['source'], ':') !== false) {
                        [, $categoryGroupIds[]] = explode(':', $settings['source']);
                    }

                    break;
                case Tags::class:
                    if (!empty($settings['source']) && strpos($settings['source'], ':') !== false) {
                        [, $tagGroupIds[]] = explode(':', $settings['source']);
                    }

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
                case Assets::class:
                    if (!empty($settings['defaultUploadLocationSource']) && strpos($settings['defaultUploadLocationSource'], ':') !== false) {
                        $default = explode(':', $settings['defaultUploadLocationSource']);
                        $settings['defaultUploadLocationSource'] = isset($folders[$default[1]]) ? $default[0] . ':' . $folders[$default[1]] : null;
                    }

                    if (!empty($settings['singleUploadLocationSource']) && strpos($settings['singleUploadLocationSource'], ':') !== false) {
                        $single = explode(':', $settings['singleUploadLocationSource']);
                        $settings['singleUploadLocationSource'] = isset($folders[$single[1]]) ? $single[0] . ':' . $folders[$single[1]] : null;
                    }


                    if (!empty($settings['sources']) && is_array($settings['sources'])) {
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
                case Entries::class:
                    if (!empty($settings['sources']) && is_array($settings['sources'])) {
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
                case Users::class:
                    if (!empty($settings['sources']) && is_array($settings['sources'])) {
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
                case Categories::class:
                    if (!empty($settings['source']) && strpos($settings['source'], ':') !== false) {
                        $source = explode(':', $settings['source']);
                        $settings['source'] = $source[0] . ':' . ($categoryGroups[$source[1]] ?? $source[1]);
                    }

                    break;
                case Tags::class:
                    if (!empty($settings['source']) && strpos($settings['source'], ':') !== false) {
                        $source = explode(':', $settings['source']);
                        $settings['source'] = $source[0] . ':' . ($tagGroups[$source[1]] ?? $source[1]);
                    }

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
