<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use Symfony\Component\Yaml\Yaml;

/**
 * m180518_173000_permissions_to_uid migration.
 */
class m180518_173000_permissions_to_uid extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from(['{{%userpermissions}}'])
            ->pairs();

        $userGroupMap = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%usergroups}}'])
            ->pairs();

        $siteMap = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%sites}}'])
            ->pairs();

        $sectionMap = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%sections}}'])
            ->pairs();

        $globalSetMap = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%globalsets}}'])
            ->pairs();

        $categoryGroupMap = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%categorygroups}}'])
            ->pairs();

        $volumeMap = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%volumes}}'])
            ->pairs();


        $relations = [
            'assignusergroup' => $userGroupMap,
            'editsite' => $siteMap,
            'createentries' => $sectionMap,
            'editentries' => $sectionMap,
            'editpeerentrydrafts' => $sectionMap,
            'editpeerentries' => $sectionMap,
            'publishentries' => $sectionMap,
            'publishpeerentrydrafts' => $sectionMap,
            'publishpeerentries' => $sectionMap,
            'deleteentries' => $sectionMap,
            'deletepeerentrydrafts' => $sectionMap,
            'deletepeerentries' => $sectionMap,
            'editglobalset' => $globalSetMap,
            'editcategories' => $categoryGroupMap,
            'viewvolume' => $volumeMap,
            'saveassetinvolume' => $volumeMap,
            'createfoldersinvolume' => $volumeMap,
            'deletefilesandfoldersinvolume' => $volumeMap,
        ];

        foreach ($permissions as $id => $permission) {
            if (preg_match('/([\w]+)(:|-)([\d]+)/i', $permission, $matches) && array_key_exists(strtolower($matches[1]), $relations) && !empty($relations[strtolower($matches[1])][$matches[3]])) {
                $permission = $matches[1].$matches[2].$relations[strtolower($matches[1])][$matches[3]];
                $this->update('{{%userpermissions}}', ['name' => $permission], ['id' => $id]);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180518_173000_permissions_to_uid cannot be reverted.\n";

        return false;
    }
}
