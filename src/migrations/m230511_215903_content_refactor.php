<?php

namespace craft\migrations;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use yii\console\Exception;

/**
 * m230511_215903_content_refactor migration.
 */
class m230511_215903_content_refactor extends BaseContentRefactorMigration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Before anything else, be absolutely certain that all custom fields' layout elements have unique UUIDs
        $uids = [];

        $projectConfigService = Craft::$app->getProjectConfig();
        $layoutConfigs = array_merge(
            array_values($projectConfigService->find(fn(array $item, string $itemPath) => str_ends_with($itemPath, '.fieldLayout'))),
            ...array_values($projectConfigService->find(fn(array $item, string $itemPath) => str_ends_with($itemPath, '.fieldLayouts'))),
        );

        foreach ($layoutConfigs as $layoutConfig) {
            foreach ($layoutConfig['tabs'] ?? [] as $tabConfig) {
                foreach ($tabConfig['elements'] ?? [] as $elementConfig) {
                    if (($elementConfig['type'] ?? null) === CustomField::class) {
                        if (empty($elementConfig['uid'])) {
                            throw new Exception('A field layout element is missing its UUID. Reinstall Craft CMS ^4.4.14 and run `utils/fix-field-layout-uids` before upgrading to Craft CMS 5.');
                        }
                        if (isset($uids[$elementConfig['uid']])) {
                            throw new Exception('A field layout element has a duplicate UUID. Reinstall Craft CMS ^4.4.14 and run `utils/fix-field-layout-uids` before upgrading to Craft CMS 5.');
                        }
                        $uids[$elementConfig['uid']] = true;
                    }
                }
            }
        }

        $this->addColumn(Table::ELEMENTS_SITES, 'title', $this->string()->after('siteId'));
        $this->addColumn(Table::ELEMENTS_SITES, 'content', $this->json()->after('uri'));
        $this->createIndex(null, Table::ELEMENTS_SITES, ['title', 'siteId']);

        $this->addColumn(Table::CHANGEDFIELDS, 'layoutElementUid', $this->uid()->after('fieldId'));

        $projectConfig = Craft::$app->getProjectConfig();

        // update addresses
        $this->updateElements(
            (new Query())->from(Table::ADDRESSES),
            Craft::$app->getAddresses()->getFieldLayout(),
        );

        // update assets
        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $this->updateElements(
                (new Query())->from(Table::ASSETS)->where(['volumeId' => $volume->id]),
                $volume->getFieldLayout(),
            );
        }

        // update categories
        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $this->updateElements(
                (new Query())->from(Table::CATEGORIES)->where(['groupId' => $group->id]),
                $group->getFieldLayout(),
            );
        }

        // update entries
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $this->updateElements(
                    (new Query())->from(Table::ENTRIES)->where(['typeId' => $entryType->id]),
                    $entryType->getFieldLayout(),
                );
            }
        }

        // update global sets
        foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
            $this->updateElements([$globalSet->id], $globalSet->getFieldLayout());
        }

        // update Matrix blocks
        $blockTypeData = (new Query())
            ->select(['id', 'uid', 'fieldLayoutId'])
            ->from('{{%matrixblocktypes}}')
            ->indexBy('uid')
            ->all();
        $indexedMatrixFieldConfigs = [];
        $matrixFieldConfigs = $projectConfig->find(
            fn(array $config) => ($config['type'] ?? null) === 'craft\fields\Matrix',
        );
        foreach ($matrixFieldConfigs as $matrixFieldPath => $matrixFieldConfig) {
            $matrixFieldUid = ArrayHelper::lastValue(explode('.', $matrixFieldPath));
            if (!isset($matrixFieldConfig['settings']['contentTable'])) {
                throw new Exception("Matrix field {$matrixFieldUid} is missing its contentTable value.");
            }
            $indexedMatrixFieldConfigs[$matrixFieldUid] = $matrixFieldConfig;
        }
        $fieldsService = Craft::$app->getFields();
        foreach ($projectConfig->get('matrixBlockTypes') ?? [] as $blockTypeUid => $blockTypeConfig) {
            if (!isset($indexedMatrixFieldConfigs[$blockTypeConfig['field']])) {
                continue;
            }
            if (!isset($blockTypeData[$blockTypeUid])) {
                throw new Exception("Matrix block type $blockTypeUid is out of sync.");
            }
            $blockTypeDatum = $blockTypeData[$blockTypeUid];
            $fieldLayout = $blockTypeDatum['fieldLayoutId']
                ? $fieldsService->getLayoutById($blockTypeDatum['fieldLayoutId'])
                : null;
            $this->updateElements(
                (new Query())->from('{{%matrixblocks}}')->where(['typeId' => $blockTypeDatum['id']]),
                $fieldLayout,
                $indexedMatrixFieldConfigs[$blockTypeConfig['field']]['settings']['contentTable'],
                sprintf('field_%s_', $blockTypeConfig['handle']),
            );
        }

        // update tags
        foreach (Craft::$app->getTags()->getAllTagGroups() as $group) {
            $this->updateElements(
                (new Query())->from(Table::TAGS)->where(['groupId' => $group->id]),
                $group->getFieldLayout(),
            );
        }

        // update users
        $this->updateElements(
            (new Query())->from(Table::USERS),
            $fieldsService->getLayoutByType(User::class),
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230511_215903_content_refactor cannot be reverted.\n";
        return false;
    }
}
