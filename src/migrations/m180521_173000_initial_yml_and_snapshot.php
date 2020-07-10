<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\services\ProjectConfig;

/**
 * m180521_173000_initial_yml_and_snapshot migration.
 */
class m180521_173000_initial_yml_and_snapshot extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::INFO, 'configMap', $this->mediumText()->null()->after('maintenance'));

        $projectConfig = Craft::$app->getProjectConfig();

        $configFile = Craft::$app->getPath()->getProjectConfigFilePath();

        if (file_exists($configFile)) {
            // Make a backup of the old config
            $backupFile = pathinfo(ProjectConfig::CONFIG_FILENAME, PATHINFO_FILENAME) . date('-Ymh-His') . '.yaml';
            echo "    > renaming project.yaml to $backupFile and moving to config backup folder ... ";
            rename($configFile, Craft::$app->getPath()->getConfigBackupPath() . '/' . $backupFile);

            // Forget everything we knew about the old config
            $projectConfig->reset();
        }

        $configData = $this->_getProjectConfigData();

        foreach ($configData as $path => $value) {
            $projectConfig->set($path, $value);
        }

        $this->dropTableIfExists('{{%systemsettings}}');

        $this->dropColumn(Table::PLUGINS, 'settings');
        $this->dropColumn(Table::PLUGINS, 'licenseKey');
        $this->dropColumn(Table::PLUGINS, 'enabled');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180521_173000_initial_yml_and_snapshot cannot be reverted.\n";

        return false;
    }

    /**
     * Return project config array.
     *
     * @return array
     */
    private function _getProjectConfigData(): array
    {
        $data = [
            'dateModified' => DateTimeHelper::currentTimeStamp(),
            'siteGroups' => $this->_getSiteGroupData(),
            'sites' => $this->_getSiteData(),
            'sections' => $this->_getSectionData(),
            'fieldGroups' => $this->_getFieldGroupData(),
            'fields' => $this->_getFieldData(),
            'matrixBlockTypes' => $this->_getMatrixBlockTypeData(),
            'volumes' => $this->_getVolumeData(),
            'categoryGroups' => $this->_getCategoryGroupData(),
            'tagGroups' => $this->_getTagGroupData(),
            'users' => $this->_getUserData(),
            'globalSets' => $this->_getGlobalSetData(),
            'plugins' => $this->_getPluginData(),
        ];

        return array_merge_recursive($data, $this->_getSystemSettingData());
    }

    /**
     * Return site data config array.
     *
     * @return array
     */
    private function _getSiteGroupData(): array
    {
        $data = [];

        $siteGroups = (new Query())
            ->select([
                'uid',
                'name',
            ])
            ->from([Table::SITEGROUPS])
            ->pairs();

        foreach ($siteGroups as $uid => $name) {
            $data[$uid] = ['name' => $name];
        }

        return $data;
    }

    /**
     * Return site data config array.
     *
     * @return array
     */
    private function _getSiteData(): array
    {
        $data = [];

        $sites = (new Query())
            ->select([
                'sites.name',
                'sites.handle',
                'sites.language',
                'sites.hasUrls',
                'sites.baseUrl',
                'sites.sortOrder',
                'sites.groupId',
                'sites.uid',
                'sites.primary',
                'siteGroups.uid AS siteGroup',
            ])
            ->from(['sites' => Table::SITES])
            ->innerJoin(['siteGroups' => Table::SITEGROUPS], '[[siteGroups.id]] = [[sites.groupId]]')
            ->all();

        foreach ($sites as $site) {
            $uid = $site['uid'];
            unset($site['uid'], $site['groupId']);

            $site['sortOrder'] = (int)$site['sortOrder'];
            $site['hasUrls'] = (bool)$site['hasUrls'];
            $site['primary'] = (bool)$site['primary'];

            $data[$uid] = $site;
        }

        return $data;
    }

    /**
     * Return section data config array.
     *
     * @return array
     */
    private function _getSectionData(): array
    {
        $sectionRows = (new Query())
            ->select([
                'sections.id',
                'sections.name',
                'sections.handle',
                'sections.type',
                'sections.enableVersioning',
                'sections.propagateEntries',
                'sections.uid',
                'structures.uid AS structure',
                'structures.maxLevels AS structureMaxLevels',
            ])
            ->from(['sections' => Table::SECTIONS])
            ->leftJoin(['structures' => Table::STRUCTURES], '[[structures.id]] = [[sections.structureId]]')
            ->all();

        $sectionData = [];

        foreach ($sectionRows as $section) {
            if (!empty($section['structure'])) {
                $section['structure'] = [
                    'uid' => $section['structure'],
                    'maxLevels' => (int)$section['structureMaxLevels'] ?: null,
                ];
            } else {
                unset($section['structure']);
            }

            $uid = $section['uid'];
            unset($section['id'], $section['structureMaxLevels'], $section['uid']);

            $section['enableVersioning'] = (bool)$section['enableVersioning'];
            $section['propagateEntries'] = (bool)$section['propagateEntries'];

            $sectionData[$uid] = $section;
            $sectionData[$uid]['entryTypes'] = [];
            $sectionData[$uid]['siteSettings'] = [];
        }

        $sectionSiteRows = (new Query())
            ->select([
                'sections_sites.enabledByDefault',
                'sections_sites.hasUrls',
                'sections_sites.uriFormat',
                'sections_sites.template',
                'sites.uid AS siteUid',
                'sections.uid AS sectionUid',
            ])
            ->from(['sections_sites' => Table::SECTIONS_SITES])
            ->innerJoin(['sites' => Table::SITES], '[[sites.id]] = [[sections_sites.siteId]]')
            ->innerJoin(['sections' => Table::SECTIONS], '[[sections.id]] = [[sections_sites.sectionId]]')
            ->all();

        foreach ($sectionSiteRows as $sectionSiteRow) {
            $sectionUid = $sectionSiteRow['sectionUid'];
            $siteUid = $sectionSiteRow['siteUid'];
            unset($sectionSiteRow['sectionUid'], $sectionSiteRow['siteUid']);

            $sectionSiteRow['hasUrls'] = (bool)$sectionSiteRow['hasUrls'];
            $sectionSiteRow['enabledByDefault'] = (bool)$sectionSiteRow['enabledByDefault'];

            $sectionData[$sectionUid]['siteSettings'][$siteUid] = $sectionSiteRow;
        }

        $entryTypeRows = (new Query())
            ->select([
                'entrytypes.fieldLayoutId',
                'entrytypes.name',
                'entrytypes.handle',
                'entrytypes.hasTitleField',
                'entrytypes.titleLabel',
                'entrytypes.titleFormat',
                'entrytypes.sortOrder',
                'entrytypes.uid',
                'sections.uid AS sectionUid',
            ])
            ->from(['entrytypes' => Table::ENTRYTYPES])
            ->innerJoin(['sections' => Table::SECTIONS], '[[sections.id]] = [[entrytypes.sectionId]]')
            ->all();

        $layoutIds = array_filter(ArrayHelper::getColumn($entryTypeRows, 'fieldLayoutId'));
        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($entryTypeRows as $entryType) {
            $uid = ArrayHelper::remove($entryType, 'uid');
            $sectionUid = ArrayHelper::remove($entryType, 'sectionUid');
            $fieldLayoutId = ArrayHelper::remove($entryType, 'fieldLayoutId');

            $entryType['hasTitleField'] = (bool)$entryType['hasTitleField'];
            $entryType['sortOrder'] = (int)$entryType['sortOrder'];

            if ($fieldLayoutId) {
                $layout = array_merge($fieldLayouts[$fieldLayoutId]);
                $layoutUid = ArrayHelper::remove($layout, 'uid');
                $entryType['fieldLayouts'] = [$layoutUid => $layout];
            }

            $sectionData[$sectionUid]['entryTypes'][$uid] = $entryType;
        }

        return $sectionData;
    }

    /**
     * Return field data config array.
     *
     * @return array
     */
    private function _getFieldGroupData(): array
    {
        $data = [];

        $fieldGroups = (new Query())
            ->select([
                'uid',
                'name',
            ])
            ->from([Table::FIELDGROUPS])
            ->pairs();

        foreach ($fieldGroups as $uid => $name) {
            $data[$uid] = ['name' => $name];
        }

        return $data;
    }

    /**
     * Return field data config array.
     *
     * @return array
     */
    private function _getFieldData(): array
    {
        $data = [];

        $fieldRows = (new Query())
            ->select([
                'fields.id',
                'fields.name',
                'fields.handle',
                'fields.context',
                'fields.instructions',
                'fields.searchable',
                'fields.translationMethod',
                'fields.translationKeyFormat',
                'fields.type',
                'fields.settings',
                'fields.uid',
                'fieldGroups.uid AS fieldGroup',
            ])
            ->from(['fields' => Table::FIELDS])
            ->leftJoin(['fieldGroups' => Table::FIELDGROUPS], '[[fieldGroups.id]] = [[fields.groupId]]')
            ->all();

        $fields = [];
        $fieldService = Craft::$app->getFields();

        // Massage the data and index by UID
        foreach ($fieldRows as $fieldRow) {
            $fieldRow['settings'] = Json::decodeIfJson($fieldRow['settings']);
            $fieldInstance = $fieldService->getFieldById($fieldRow['id']);
            $fieldRow['contentColumnType'] = $fieldInstance->getContentColumnType();

            $fieldRow['searchable'] = (bool)$fieldRow['searchable'];

            $fields[$fieldRow['uid']] = $fieldRow;
        }

        foreach ($fields as $field) {
            $fieldUid = $field['uid'];
            unset($field['id'], $field['uid']);
            $data[$fieldUid] = $field;
        }

        return $data;
    }

    /**
     * Return matrix block type data config array.
     *
     * @return array
     */
    private function _getMatrixBlockTypeData(): array
    {
        $data = [];

        $matrixBlockTypes = (new Query())
            ->select([
                'bt.fieldId',
                'bt.fieldLayoutId',
                'bt.name',
                'bt.handle',
                'bt.sortOrder',
                'bt.uid',
                'f.uid AS field',
            ])
            ->from(['bt' => Table::MATRIXBLOCKTYPES])
            ->innerJoin(['f' => Table::FIELDS], '[[f.id]] = [[bt.fieldId]]')
            ->all();

        $layoutIds = [];
        $blockTypeData = [];

        foreach ($matrixBlockTypes as $matrixBlockType) {
            $fieldId = $matrixBlockType['fieldId'];
            unset($matrixBlockType['fieldId']);

            $layoutIds[] = $matrixBlockType['fieldLayoutId'];

            $matrixBlockType['sortOrder'] = (int)$matrixBlockType['sortOrder'];

            $blockTypeData[$fieldId][$matrixBlockType['uid']] = $matrixBlockType;
        }

        $matrixFieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($blockTypeData as &$blockTypes) {
            foreach ($blockTypes as &$blockType) {
                $blockTypeUid = $blockType['uid'];
                $layout = $matrixFieldLayouts[$blockType['fieldLayoutId']];
                unset($blockType['uid'], $blockType['fieldLayoutId']);
                $blockType['fieldLayouts'] = [$layout['uid'] => ['tabs' => $layout['tabs']]];
                $data[$blockTypeUid] = $blockType;
            }
        }

        return $data;
    }

    /**
     * Return volume data config array.
     *
     * @return array
     */
    private function _getVolumeData(): array
    {
        $volumes = (new Query())
            ->select([
                'volumes.fieldLayoutId',
                'volumes.name',
                'volumes.handle',
                'volumes.type',
                'volumes.hasUrls',
                'volumes.url',
                'volumes.settings',
                'volumes.sortOrder',
                'volumes.uid',
            ])
            ->from(['volumes' => Table::VOLUMES])
            ->all();

        $layoutIds = [];

        foreach ($volumes as $volume) {
            $layoutIds[] = $volume['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        $data = [];

        foreach ($volumes as $volume) {
            if (isset($fieldLayouts[$volume['fieldLayoutId']])) {
                $layoutUid = $fieldLayouts[$volume['fieldLayoutId']]['uid'];
                unset($fieldLayouts[$volume['fieldLayoutId']]['uid']);
                $volume['fieldLayouts'] = [$layoutUid => $fieldLayouts[$volume['fieldLayoutId']]];
            }

            $volume['settings'] = Json::decodeIfJson($volume['settings']);
            $uid = $volume['uid'];
            unset($volume['fieldLayoutId'], $volume['uid']);

            $volume['hasUrls'] = (bool)$volume['hasUrls'];
            $volume['sortOrder'] = (int)$volume['sortOrder'];

            $data[$uid] = $volume;
        }

        return $data;
    }

    /**
     * Return user group data config array.
     *
     * @return array
     */
    private function _getUserData(): array
    {
        $data = [];

        $layoutId = (new Query())
            ->select(['id'])
            ->from([Table::FIELDLAYOUTS])
            ->where(['type' => User::class])
            ->scalar();

        if ($layoutId) {
            $layouts = array_values($this->_generateFieldLayoutArray([$layoutId]));
            $layout = reset($layouts);
            $uid = $layout['uid'];
            unset($layout['uid']);
            $data['fieldLayouts'] = [$uid => $layout];
        }

        $groups = (new Query())
            ->select(['id', 'name', 'handle', 'uid'])
            ->from([Table::USERGROUPS])
            ->all();

        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from([Table::USERPERMISSIONS])
            ->pairs();

        $groupPermissions = (new Query())
            ->select(['permissionId', 'groupId'])
            ->from([Table::USERPERMISSIONS_USERGROUPS])
            ->all();

        $permissionList = [];

        foreach ($groupPermissions as $groupPermission) {
            $permissionList[$groupPermission['groupId']][] = $permissions[$groupPermission['permissionId']];
        }

        foreach ($groups as $group) {
            $data['groups'][$group['uid']] = [
                'name' => $group['name'],
                'handle' => $group['handle'],
                'permissions' => $permissionList[$group['id']] ?? []
            ];
        }

        $data['permissions'] = array_unique(array_values($permissions));

        return $data;
    }

    /**
     * Return user setting data config array.
     *
     * @return array
     */
    private function _getSystemSettingData(): array
    {
        $settings = (new Query())
            ->select([
                'category',
                'settings',
            ])
            ->from(['{{%systemsettings}}'])
            ->pairs();

        foreach ($settings as &$setting) {
            $setting = Json::decodeIfJson($setting);
        }

        return $settings;
    }

    /**
     * Return category group data config array.
     *
     * @return array
     */
    private function _getCategoryGroupData(): array
    {
        $groupRows = (new Query())
            ->select([
                'groups.name',
                'groups.handle',
                'groups.uid',
                'groups.fieldLayoutId',
                'structures.uid AS structure',
                'structures.maxLevels AS structureMaxLevels',
            ])
            ->from(['groups' => Table::CATEGORYGROUPS])
            ->leftJoin(['structures' => Table::STRUCTURES], '[[structures.id]] = [[groups.structureId]]')
            ->all();

        $groupData = [];

        $layoutIds = [];

        foreach ($groupRows as $group) {
            $layoutIds[] = $group['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($groupRows as $group) {
            if (!empty($group['structure'])) {
                $group['structure'] = [
                    'uid' => $group['structure'],
                    'maxLevels' => (int)$group['structureMaxLevels'] ?: null,
                ];
            } else {
                unset($group['structure']);
            }

            if (isset($fieldLayouts[$group['fieldLayoutId']])) {
                $layoutUid = $fieldLayouts[$group['fieldLayoutId']]['uid'];
                unset($fieldLayouts[$group['fieldLayoutId']]['uid']);
                $group['fieldLayouts'] = [$layoutUid => $fieldLayouts[$group['fieldLayoutId']]];
            }

            $uid = $group['uid'];
            unset($group['structureMaxLevels'], $group['uid'], $group['fieldLayoutId']);

            $groupData[$uid] = $group;
            $groupData[$uid]['siteSettings'] = [];
        }

        $groupSiteRows = (new Query())
            ->select([
                'groups_sites.hasUrls',
                'groups_sites.uriFormat',
                'groups_sites.template',
                'sites.uid AS siteUid',
                'groups.uid AS groupUid',
            ])
            ->from(['groups_sites' => Table::CATEGORYGROUPS_SITES])
            ->innerJoin(['sites' => Table::SITES], '[[sites.id]] = [[groups_sites.siteId]]')
            ->innerJoin(['groups' => Table::CATEGORYGROUPS], '[[groups.id]] = [[groups_sites.groupId]]')
            ->all();

        foreach ($groupSiteRows as $groupSiteRow) {
            $groupUid = $groupSiteRow['groupUid'];
            $siteUid = $groupSiteRow['siteUid'];
            unset($groupSiteRow['siteUid'], $groupSiteRow['groupUid']);

            $groupSiteRow['hasUrls'] = (bool)$groupSiteRow['hasUrls'];

            $groupData[$groupUid]['siteSettings'][$siteUid] = $groupSiteRow;
        }

        return $groupData;
    }

    /**
     * Return tag group data config array.
     *
     * @return array
     */
    private function _getTagGroupData(): array
    {
        $groupRows = (new Query())
            ->select([
                'groups.name',
                'groups.handle',
                'groups.uid',
                'groups.fieldLayoutId',
            ])
            ->from(['groups' => Table::TAGGROUPS])
            ->all();

        $groupData = [];
        $layoutIds = [];

        foreach ($groupRows as $group) {
            $layoutIds[] = $group['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($groupRows as $group) {
            if (isset($fieldLayouts[$group['fieldLayoutId']])) {
                $layoutUid = $fieldLayouts[$group['fieldLayoutId']]['uid'];
                unset($fieldLayouts[$group['fieldLayoutId']]['uid']);
                $group['fieldLayouts'] = [$layoutUid => $fieldLayouts[$group['fieldLayoutId']]];
            }

            $uid = $group['uid'];
            unset($group['uid'], $group['fieldLayoutId']);

            $groupData[$uid] = $group;
        }

        return $groupData;
    }

    /**
     * Return global set data config array.
     *
     * @return array
     */
    private function _getGlobalSetData(): array
    {
        $setRows = (new Query())
            ->select([
                'sets.name',
                'sets.handle',
                'sets.uid',
                'sets.fieldLayoutId',
            ])
            ->from(['sets' => Table::GLOBALSETS])
            ->all();

        $setData = [];
        $layoutIds = [];

        foreach ($setRows as $setRow) {
            $layoutIds[] = $setRow['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($setRows as $setRow) {
            if (isset($fieldLayouts[$setRow['fieldLayoutId']])) {
                $layoutUid = $fieldLayouts[$setRow['fieldLayoutId']]['uid'];
                unset($fieldLayouts[$setRow['fieldLayoutId']]['uid']);
                $setRow['fieldLayouts'] = [$layoutUid => $fieldLayouts[$setRow['fieldLayoutId']]];
            }

            $uid = $setRow['uid'];
            unset($setRow['uid'], $setRow['fieldLayoutId']);

            $setData[$uid] = $setRow;
        }

        return $setData;
    }

    /**
     * Return plugin data config array
     *
     * @return array
     */
    private function _getPluginData(): array
    {
        $plugins = (new Query())
            ->select([
                'handle',
                'settings',
                'licenseKey',
                'enabled',
                'schemaVersion',
            ])
            ->from([Table::PLUGINS])
            ->all();

        $pluginData = [];

        foreach ($plugins as $plugin) {
            $pluginData[$plugin['handle']] = [
                'settings' => is_string($plugin['settings']) ? Json::decodeIfJson($plugin['settings']) : null,
                'licenseKey' => $plugin['licenseKey'],
                'enabled' => $plugin['enabled'],
                'schemaVersion' => $plugin['schemaVersion'],
            ];
        }

        return $pluginData;
    }

    /**
     * Generate field layout config data for a list of array ids
     *
     * @param int[] $layoutIds
     *
     * @return array
     */
    private function _generateFieldLayoutArray(array $layoutIds): array
    {
        // Get all the UIDs
        $fieldLayoutUids = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::FIELDLAYOUTS])
            ->where(['id' => $layoutIds])
            ->pairs();

        $fieldLayouts = [];
        foreach ($fieldLayoutUids as $id => $uid) {
            $fieldLayouts[$id] = [
                'uid' => $uid,
                'tabs' => [],
            ];
        }

        // Get the tabs and fields
        $fieldRows = (new Query())
            ->select([
                'fields.handle',
                'fields.uid AS fieldUid',
                'layoutFields.fieldId',
                'layoutFields.required',
                'layoutFields.sortOrder AS fieldOrder',
                'tabs.id AS tabId',
                'tabs.name as tabName',
                'tabs.sortOrder AS tabOrder',
                'tabs.uid AS tabUid',
                'layouts.id AS layoutId',
            ])
            ->from(['layoutFields' => Table::FIELDLAYOUTFIELDS])
            ->innerJoin(['tabs' => Table::FIELDLAYOUTTABS], '[[tabs.id]] = [[layoutFields.tabId]]')
            ->innerJoin(['layouts' => Table::FIELDLAYOUTS], '[[layouts.id]] = [[layoutFields.layoutId]]')
            ->innerJoin(['fields' => Table::FIELDS], '[[fields.id]] = [[layoutFields.fieldId]]')
            ->where(['layouts.id' => $layoutIds])
            ->orderBy(['tabs.sortOrder' => SORT_ASC, 'layoutFields.sortOrder' => SORT_ASC])
            ->all();

        foreach ($fieldRows as $fieldRow) {
            $layout = &$fieldLayouts[$fieldRow['layoutId']];

            if (empty($layout['tabs'][$fieldRow['tabUid']])) {
                $layout['tabs'][$fieldRow['tabUid']] =
                    [
                        'name' => $fieldRow['tabName'],
                        'sortOrder' => (int)$fieldRow['tabOrder'],
                    ];
            }

            $tab = &$layout['tabs'][$fieldRow['tabUid']];

            $field['required'] = (bool)$fieldRow['required'];
            $field['sortOrder'] = (int)$fieldRow['fieldOrder'];

            $tab['fields'][$fieldRow['fieldUid']] = $field;
        }

        // Get rid of UIDs
        foreach ($fieldLayouts as &$fieldLayout) {
            $fieldLayout['tabs'] = array_values($fieldLayout['tabs']);
        }

        return $fieldLayouts;
    }
}
