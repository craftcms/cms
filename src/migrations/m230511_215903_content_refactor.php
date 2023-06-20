<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\fields\Matrix;
use craft\services\ProjectConfig;
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
        $fieldsService = Craft::$app->getFields();
        foreach ($fieldsService->getAllLayouts() as $fieldLayout) {
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                if (!isset($layoutElement->uid)) {
                    throw new Exception('A field layout element is missing its UUID. Reinstall Craft CMS ^4.4.14 and run `utils/fix-field-layout-uids` before upgrading to Craft CMS 5.');
                }
                if (isset($uids[$layoutElement->uid])) {
                    throw new Exception('A field layout element has a duplicate UUID. Reinstall Craft CMS ^4.4.14 and run `utils/fix-field-layout-uids` before upgrading to Craft CMS 5.');
                }
                $uids[$layoutElement->uid] = true;
            }
        }

        $this->addColumn(Table::ELEMENTS_SITES, 'title', $this->string()->after('siteId'));
        $this->addColumn(Table::ELEMENTS_SITES, 'content', $this->json()->after('uri'));
        $this->createIndex(null, Table::ELEMENTS_SITES, ['title', 'siteId']);

        $this->addColumn(Table::CHANGEDFIELDS, 'layoutElementUid', $this->uid()->after('fieldId'));

        // update addresses
        $this->updateElements(
            (new Query())->from(Table::ADDRESSES),
            Craft::$app->getAddresses()->getLayout(),
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
        /** @var Matrix[] $matrixFields */
        $matrixFields = $fieldsService->getFieldsByType(Matrix::class);
        foreach ($matrixFields as $field) {
            foreach ($field->getBlockTypes() as $blockType) {
                $this->updateElements(
                    (new Query())->from(Table::MATRIXBLOCKS)->where(['typeId' => $blockType->id]),
                    $blockType->getFieldLayout(),
                    $field->contentTable,
                    sprintf('field_%s_', $blockType->handle),
                );
            }

            // if the field is global, drop the contentTable value from its config
            if ($field->context === 'global') {
                Craft::$app->getProjectConfig()->remove(sprintf('%s.%s.settings.contentTable', ProjectConfig::PATH_FIELDS, $field->uid));
            }
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
