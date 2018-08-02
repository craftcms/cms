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
 * m180521_173000_initial_yml_and_snapshot migration.
 */
class m180521_173000_initial_yml_and_snapshot extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%info}}', 'configSnapshot', $this->mediumText()->null());
        $this->addColumn('{{%info}}', 'configMap', $this->mediumText()->null());

        $path = Craft::$app->getPath()->getConfigPath();

        $data = $this->_getProjectConfigData();

        $yaml = Yaml::dump($data, 20, 2);
        $destination = $path.'/system.yml';
        FileHelper::writeToFile($destination, $yaml);

        $modTime = FileHelper::lastModifiedTime($destination);

        $snapshot = serialize($data);

        $nodes = array_keys($data);
        $configMap = [];

        foreach ($nodes as $node) {
            $configMap[$node] = $destination;
        }

        $this->update('{{%info}}', [
            'configSnapshot' => $snapshot,
            'configMap' => Json::encode($configMap)
        ]);

        Craft::$app->getCache()->set(ProjectConfig::CACHE_KEY, [$destination => $modTime], ProjectConfig::CACHE_DURATION);

        $this->dropTableIfExists('{{%systemsettings}}');

        return true;
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
            'sites' => $this->_getSiteData(),
            'sections' => $this->_getSectionData(),
            'fields' => $this->_getFieldData(),
            'volumes' => $this->_getVolumeData(),
            'categoryGroups' => $this->_getCategoryGroupData(),
            'tagGroups' => $this->_getTagGroupData(),
            'users' => $this->_getUserData(),
            'globalSets' => $this->_getGlobalSetData(),
        ];

        //$data = array_merge_recursive($data, $this->_getSystemSettingData());

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

        $siteGroups = (new Query())
            ->select([
                'id',
                'name',
                'uid',
            ])
            ->from('{{%sitegroups}}')
            ->all();

        $siteGroupMap = [];
        foreach ($siteGroups as $siteGroup) {
            $siteGroup['sites'] = [];
            $siteGroupMap[$siteGroup['id']] = $siteGroup['uid'];

            $data[$siteGroupMap[$siteGroup['id']]] = $siteGroup;
            unset($data[$siteGroupMap[$siteGroup['id']]]['id'], $data[$siteGroupMap[$siteGroup['id']]]['uid']);
        }

        $sites = (new Query())
            ->select([
                'name',
                'handle',
                'language',
                'hasUrls',
                'baseUrl',
                'sortOrder',
                'groupId',
                'uid',
                'primary'
            ])
            ->from('{{%sites}}')
            ->all();

        foreach ($sites as $site) {
            $target = $siteGroupMap[$site['groupId']];
            $uid = $site['uid'];

            unset($site['groupId'], $site['uid']);

            $data[$target]['sites'][$uid] = $site;
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
            ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[sections.structureId]]')
            ->from(['{{%sections}} sections'])
            ->all();

        $sectionData = [];

        foreach ($sectionRows as $section) {
            if (!empty($section['structure'])) {
                $section['structure'] = [
                    'uid' => $section['structure'],
                    'maxLevels' => $section['structureMaxLevels']
                ];
            } else {
                unset($section['structure']);
            }


            $uid = $section['uid'];
            unset($section['id'], $section['structureMaxLevels'], $section['uid']);

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
            ->from('{{%sections_sites}} sections_sites')
            ->innerJoin('{{%sites}} sites', '[[sites.id]] = [[sections_sites.siteId]]')
            ->innerJoin('{{%sections}} sections', '[[sections.id]] = [[sections_sites.sectionId]]')
            ->all();

        foreach ($sectionSiteRows as $sectionSiteRow) {
            $sectionUid = $sectionSiteRow['sectionUid'];
            $siteUid = $sectionSiteRow['siteUid'];
            unset($sectionSiteRow['sectionUid'], $sectionSiteRow['siteUid']);
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
                'entrytypes.uid',
                'sections.uid AS sectionUid'
            ])
            ->from(['{{%entrytypes}} as entrytypes'])
            ->innerJoin('{{%sections}} sections', '[[sections.id]] = [[entrytypes.sectionId]]')
            ->all();

        $layoutIds = [];
        foreach ($entryTypeRows as $entryType) {
            $layoutIds[] = $entryType['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($entryTypeRows as $entryType) {
            $layout = $fieldLayouts[$entryType['fieldLayoutId']];

            $layoutUid = $layout['uid'];
            $sectionUid = $entryType['sectionUid'];
            $uid = $entryType['uid'];

            unset($entryType['fieldLayoutId'], $entryType['sectionUid'], $entryType['uid'], $layout['uid']);

            $entryType['fieldLayouts'] = [$layoutUid => $layout];
            $sectionData[$sectionUid]['entryTypes'][$uid] = $entryType;
        }

        return $sectionData;
    }

    /**
     * Return field data config array.
     *
     * @return array
     */
    private function _getFieldData(): array
    {
        $data = [];

        $fieldGroups = (new Query())
            ->select([
                'id',
                'name',
                'uid',
            ])
            ->from(['{{%fieldgroups}}'])
            ->all();

        $fieldGroupMap = [];

        foreach ($fieldGroups as $fieldGroup) {
            $fieldGroup['fields'] = [];
            $fieldGroupMap[$fieldGroup['id']] = $fieldGroup['uid'];
            $data[$fieldGroupMap[$fieldGroup['id']]] = $fieldGroup;
            unset($data[$fieldGroupMap[$fieldGroup['id']]]['id'], $data[$fieldGroupMap[$fieldGroup['id']]]['uid']);
        }

        $fieldRows= (new Query())
            ->select([
                'id',
                'groupId',
                'name',
                'handle',
                'context',
                'instructions',
                'translationMethod',
                'translationKeyFormat',
                'type',
                'settings',
                'uid',
            ])
            ->from('{{%fields}}')
            ->all();

        $fields = [];
        $fieldService = Craft::$app->getFields();

        // Index by UID
        foreach ($fieldRows as $fieldRow) {
            $fieldRow['settings'] = Json::decodeIfJson($fieldRow['settings']);
            $fieldInstance = $fieldService->getFieldById($fieldRow['id']);
            $fieldRow['contentColumnType'] = $fieldInstance->getContentColumnType();
            $fields[$fieldRow['uid']] = $fieldRow;

        }

        $matrixBlockTypes = (new Query())
            ->select([
                'fieldId',
                'fieldLayoutId',
                'name',
                'handle',
                'sortOrder',
                'uid',
            ])
            ->from('{{%matrixblocktypes}}')
            ->all();

        $layoutIds = [];
        $blockTypeData = [];

        foreach ($matrixBlockTypes as $matrixBlockType) {
            $fieldId = $matrixBlockType['fieldId'];
            unset($matrixBlockType['fieldId']);

            $layoutIds[] = $matrixBlockType['fieldLayoutId'];
            $blockTypeData[$fieldId][$matrixBlockType['uid']] = $matrixBlockType;
        }

        $matrixFieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        // Nest the field definitions inside Matrix block type definitions.
        foreach ($matrixBlockTypes as $matrixBlockType) {
            $fieldId = $matrixBlockType['fieldId'];
            $layoutUid = $matrixFieldLayouts[$matrixBlockType['fieldLayoutId']]['uid'];

            foreach ($matrixFieldLayouts[$matrixBlockType['fieldLayoutId']]['tabs'] as &$tab) {
                $tabFields = [];

                foreach ($tab['fields'] as $fieldUid => $field) {
                    // Replace the dependency with actual definition
                    $fieldDefinition = $fields[$fieldUid];

                    unset($fieldDefinition['uid'], $fieldDefinition['id'], $fieldDefinition['groupId']);

                    $tabFields[$fieldUid] = [
                        'sortOrder' => $field['sortOrder'],
                        'required' => $field['required'],
                        'field' => $fieldDefinition
                    ];
                }

                $tab['fields'] = $tabFields;
            }

            $blockTypeData[$fieldId][$matrixBlockType['uid']]['layouts'] = [$layoutUid => $matrixFieldLayouts[$matrixBlockType['fieldLayoutId']]];
            unset($blockTypeData[$fieldId][$matrixBlockType['uid']]['fieldLayoutId'], $blockTypeData[$fieldId][$matrixBlockType['uid']]['layouts'][$layoutUid]['uid']);

        }

        foreach ($blockTypeData as &$blockTypes) {
            foreach ($blockTypes as &$blockType) {
                unset($blockType['uid']);
            }
        }

        foreach ($fields as $field) {
            if (empty($field['groupId'])) {
                continue;
            }

            if ($field['type'] === 'craft\fields\Matrix') {
                $field['blockTypes'] = $blockTypeData[$field['id']];
            }
            $fieldUid = $field['uid'];
            $groupId = $field['groupId'];
            unset($field['id'], $field['uid'], $field['groupId']);
            $data[$fieldGroupMap[$groupId]]['fields'][$fieldUid] = $field;
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
                'volumes.uid'
            ])
            ->from(['{{%volumes}} volumes'])
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
            ->from(['{{%fieldlayouts}}'])
            ->where(['type' => 'craft\\elements\\User'])
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
            ->from(['{{%usergroups}}'])
            ->all();

        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from(['{{%userpermissions}}'])
            ->pairs();

        $groupPermissions = (new Query())
            ->select(['permissionId', 'groupId'])
            ->from(['{{%userpermissions_usergroups}}'])
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
            ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[groups.structureId]]')
            ->from(['{{%categorygroups}} groups'])
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
                    'maxLevels' => $group['structureMaxLevels']
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
            ->from('{{%categorygroups_sites}} groups_sites')
            ->innerJoin('{{%sites}} sites', '[[sites.id]] = [[groups_sites.siteId]]')
            ->innerJoin('{{%categorygroups}} groups', '[[groups.id]] = [[groups_sites.groupId]]')
            ->all();

        foreach ($groupSiteRows as $groupSiteRow) {
            $groupUid = $groupSiteRow['groupUid'];
            $siteUid = $groupSiteRow['siteUid'];
            unset($groupSiteRow['siteUid'], $groupSiteRow['groupUid']);
            $groupData[$groupUid]['siteSettings'][$siteUid] = $groupSiteRow;
        }

        return $groupData;
    }

    /**
     * Return tag group data config array.
     *
     * @return array
     */
    private function _getTagGroupData(): array {
        $groupRows = (new Query())
            ->select([
                'groups.name',
                'groups.handle',
                'groups.uid',
                'groups.fieldLayoutId',
            ])
            ->from(['{{%taggroups}} groups'])
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
    private function _getGlobalSetData(): array {
        $setRows = (new Query())
            ->select([
                'sets.name',
                'sets.handle',
                'sets.uid',
                'sets.fieldLayoutId',
            ])
            ->from(['{{%globalsets}} sets'])
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
     * Generate field layout config data for a list of array ids
     *
     * @param int[] $layoutIds
     *
     * @return array
     */
    private function _generateFieldLayoutArray(array $layoutIds): array
    {
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
                'layouts.uid AS layoutUid',
            ])
            ->from(['{{%fieldlayoutfields}} AS layoutFields'])
            ->innerJoin('{{%fieldlayouttabs}} AS tabs', '[[layoutFields.tabId]] = [[tabs.id]]')
            ->innerJoin('{{%fieldlayouts}} AS layouts', '[[layoutFields.layoutId]] = [[layouts.id]]')
            ->innerJoin('{{%fields}} AS fields', '[[layoutFields.fieldId]] = [[fields.id]]')
            ->where(['layouts.id' => $layoutIds])
            ->orderBy(['tabs.sortOrder' => SORT_ASC, 'layoutFields.sortOrder' => SORT_ASC])
            ->all();

        $fieldLayouts = [];

        foreach ($fieldRows as $fieldRow) {
            $fieldLayouts[$fieldRow['layoutId']]['uid'] = $fieldRow['layoutUid'];
            $layout = &$fieldLayouts[$fieldRow['layoutId']];
            $layout['uid'] = $fieldRow['layoutUid'];

            if (empty($layout['tabs'])) {
                $layout['tabs'] = [];
            }

            if (empty($layout['tabs'][$fieldRow['tabUid']])) {
                $layout['tabs'][$fieldRow['tabUid']] =
                    [
                        'name' => $fieldRow['tabName'],
                        'sortOrder' => $fieldRow['tabOrder'],
                    ];
            }

            $tab = &$layout['tabs'][$fieldRow['tabUid']];

            $field['required'] = $fieldRow['required'];
            $field['sortOrder'] = $fieldRow['fieldOrder'];

            $tab['fields'][$fieldRow['fieldUid']] = $field;
        }

        // Get rid of UIDs
        foreach ($fieldLayouts as &$fieldLayout) {
            $fieldLayout['tabs'] = array_values($fieldLayout['tabs']);
        }

        return $fieldLayouts;
    }
}
