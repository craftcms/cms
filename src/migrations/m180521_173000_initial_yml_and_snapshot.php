<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\FileHelper;
use craft\helpers\Json;
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

        $yaml = Yaml::dump($data, 10, 2);
        $destination = $path.'/system.yml';
        FileHelper::writeToFile($destination, $yaml);

        $modTime = FileHelper::lastModifiedTime($destination);

        $snapshot = serialize($data);
        $map = Json::encode($this->_getUidMap($data));

        $this->update('{{%info}}', [
            'configSnapshot' => $snapshot,
            'configMap' => $map
        ]);

        Craft::$app->getCache()->set(ProjectConfig::CACHE_KEY, [$destination => $modTime], ProjectConfig::CACHE_DURATION);

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
            'volumes' => $this->_getVolumeData()
        ];

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
            unset($siteGroup['id']);
            $data[$siteGroup['uid']] = $siteGroup;
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
            unset($site['groupId']);

            $data[$target]['sites'][$site['uid']] = $site;
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

            unset($section['id'], $section['structureMaxLevels']);

            $sectionData[$section['uid']] = $section;
            $sectionData[$section['uid']]['entryTypes'] = [];
            $sectionData[$section['uid']]['siteSettings'] = [];
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
            $sectionData[$sectionSiteRow['sectionUid']]['siteSettings'][$sectionSiteRow['siteUid']] = $sectionSiteRow;
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
            $entryType['fieldLayout'] = $fieldLayouts[$entryType['fieldLayoutId']];
            $uid = $entryType['sectionUid'];
            unset($entryType['fieldLayoutId'], $entryType['sectionUid']);

            $sectionData[$uid]['entryTypes'][$entryType['uid']] = $entryType;
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
            unset($fieldGroup['id']);
            $data[$fieldGroup['uid']] = $fieldGroup;
        }

        $fields = (new Query())
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

        foreach ($matrixBlockTypes as $matrixBlockType) {
            $layoutId = $matrixBlockType['fieldLayoutId'];
            $fieldId = $matrixBlockType['fieldId'];
            unset($matrixBlockType['fieldId']);
            $layoutIds[] = $layoutId;
            $blockTypeData[$fieldId][$matrixBlockType['uid']] = $matrixBlockType;
        }

        $matrixFieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        foreach ($matrixBlockTypes as $matrixBlockType) {
            $blockTypeData[$fieldId][$matrixBlockType['uid']]['layout'] = $matrixFieldLayouts[$matrixBlockType['fieldLayoutId']];
            unset($blockTypeData[$fieldId][$matrixBlockType['uid']]['fieldLayoutId']);

        }

        foreach ($fields as $field) {
            if (empty($field['groupId'])) {
                continue;
            }

            if ($field['type'] === 'craft\fields\Matrix') {
                $field['blocks'] = $blockTypeData[$field['id']];
            }

            unset($field['id']);
            $data[$fieldGroupMap[$field['groupId']]]['fields'][$field['uid']] = $field;
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
                'folder.uid as folderUid',
            ])
            ->leftJoin('{{%volumefolders}} folder', '[[folder.volumeId]] = [[volumes.id]]')
            ->where(['folder.parentId' => null])
            ->from(['{{%volumes}} volumes'])
            ->all();

        $layoutIds = [];

        foreach ($volumes as $volume) {
            $layoutIds[] = $volume['fieldLayoutId'];
        }

        $fieldLayouts = $this->_generateFieldLayoutArray($layoutIds);

        $data = [];

        foreach ($volumes as $volume) {
            $volume['folder'] = ['uid' => $volume['folderUid']];
            if (isset($fieldLayouts[$volume['fieldLayoutId']])) {
                $volume['layout'] = $fieldLayouts[$volume['fieldLayoutId']];
            } else {
                $volume['layout'] = [];
            }
            unset($volume['fieldLayoutId'], $volume['folderUid']);
            $data[$volume['uid']] = $volume;
        }

        return $data;
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

            $field['fieldUid'] = $fieldRow['fieldUid'];
            $field['required'] = $fieldRow['required'];
            $field['sortOrder'] = $fieldRow['fieldOrder'];

            $tab['fields'][] = $field;
        }

        // get rid of tab UIDs as keys.
        foreach ($fieldLayouts as &$fieldLayout) {
            $fieldLayout['tabs'] = array_values($fieldLayout['tabs']);
        }

        return $fieldLayouts;
    }

    /**
     * Return the UID map for the project config array.
     *
     * @param array $data
     *
     * @return array
     */
    private function _getUidMap(array $data): array
    {
        $paths = [];

        $extractLocation = function ($level, $currentPath) use (&$paths, &$extractLocation) {
            foreach ($level as $key => $element) {
                // Record top level nodes and all UIDs.
                $path = $currentPath ?? Craft::$app->getPath()->getConfigPath().'/system.yml';

                if ($key === 'uid' || empty($currentPath)) {
                    // For top-level nodes we need the key
                    if (is_array($element)) {
                        $paths[$key] = $path;
                    } else {
                        $paths[$element] = $path;
                    }
                }

                if (is_array($element)) {
                    $extractLocation($element, $path.($currentPath ? '.' : '/').$key);
                }
            }
        };

        $extractLocation($data, null);

        return $paths;
    }
}
