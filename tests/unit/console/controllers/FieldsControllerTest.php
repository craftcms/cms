<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\console\controllers;

use Craft;
use craft\base\FieldInterface;
use craft\console\controllers\FieldsController;
use craft\elements\conditions\ElementCondition;
use craft\elements\ContentBlock as ContentBlockElement;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\conditions\LightswitchFieldConditionRule;
use craft\fields\ContentBlock;
use craft\fields\Lightswitch;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\test\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Tests for the `fields/merge` command.
 *
 * @see https://github.com/craftcms/cms/issues/19901
 */
class FieldsControllerTest extends TestCase
{
    private Lightswitch $persistingField;

    private Lightswitch $outgoingField;

    private Lightswitch $conditionalField;

    private ContentBlock $contentBlockField;

    private EntryType $entryType;

    protected function _before(): void
    {
        parent::_before();

        $suffix = StringHelper::randomString(6, false);
        $this->persistingField = $this->_createLightswitch("Toggle A $suffix", "toggleA$suffix");
        $this->outgoingField = $this->_createLightswitch("Toggle B $suffix", "toggleB$suffix");
        $this->conditionalField = $this->_createLightswitch("Toggle C $suffix", "toggleC$suffix");

        $this->contentBlockField = new ContentBlock([
            'name' => "Block $suffix",
            'handle' => "block$suffix",
        ]);
        $this->contentBlockField->setFieldLayout($this->_createLayout(ContentBlockElement::class));
        if (! Craft::$app->getFields()->saveField($this->contentBlockField)) {
            throw new RuntimeException('Couldn’t save the Content Block field.');
        }

        $this->entryType = new EntryType([
            'name' => "Entry Type $suffix",
            'handle' => "entryType$suffix",
        ]);
        $this->entryType->setFieldLayout($this->_createLayout(Entry::class));
        if (! Craft::$app->getEntries()->saveEntryType($this->entryType)) {
            throw new RuntimeException('Couldn’t save the entry type.');
        }
    }

    protected function _after(): void
    {
        $fieldsService = Craft::$app->getFields();
        Craft::$app->getEntries()->deleteEntryType($this->entryType);
        $fieldsService->deleteField($this->contentBlockField);
        foreach ([$this->persistingField, $this->outgoingField, $this->conditionalField] as $field) {
            if ($fieldsService->getFieldById($field->id)) {
                $fieldsService->deleteField($field);
            }
        }

        parent::_after();
    }

    public function test_merge_updates_field_usages(): void
    {
        $fieldsService = Craft::$app->getFields();
        $layouts = $fieldsService->findFieldUsages($this->outgoingField);
        self::assertCount(2, $layouts);

        $controller = new FieldsController('fields', Craft::$app);
        $method = new ReflectionMethod($controller, 'updateFieldUsages');
        $method->invoke($controller, $this->persistingField, $this->outgoingField, $layouts);

        // The merge command deletes the outgoing field right after updating its usages
        $fieldsService->deleteField($this->outgoingField);

        $contentBlockField = $fieldsService->getFieldById($this->contentBlockField->id);
        self::assertInstanceOf(ContentBlock::class, $contentBlockField);
        $contentBlockLayout = $contentBlockField->getFieldLayout();
        $entryTypeLayout = Craft::$app->getEntries()->getEntryTypeById($this->entryType->id)->getFieldLayout();

        // Field settings are stored as packed associative arrays
        $projectConfig = Craft::$app->getProjectConfig();
        $contentBlockSettings = ProjectConfigHelper::unpackAssociativeArrays($projectConfig->get("fields.$contentBlockField->uid.settings"));
        $entryTypeConfig = ProjectConfigHelper::unpackAssociativeArrays($projectConfig->get("entryTypes.{$this->entryType->uid}"));
        $layoutConfigs = [
            'Content Block' => [$contentBlockLayout, $contentBlockSettings['fieldLayouts'][$contentBlockLayout->uid] ?? null],
            'entry type' => [$entryTypeLayout, $entryTypeConfig['fieldLayouts'][$entryTypeLayout->uid] ?? null],
        ];

        foreach ($layoutConfigs as $name => [$layout, $config]) {
            self::assertIsArray($config, "The $name layout is missing from the project config.");
            foreach ([
                'saved layout' => $layout->getConfig(),
                'project config' => $config,
            ] as $source => $layoutConfig) {
                $elements = $layoutConfig['tabs'][0]['elements'];
                self::assertSame(
                    [$this->persistingField->uid, $this->conditionalField->uid],
                    array_column($elements, 'fieldUid'),
                    "The {$name}’s {$source} still references the outgoing field.",
                );
                self::assertSame(
                    $this->persistingField->uid,
                    $elements[1]['elementCondition']['conditionRules'][0]['fieldUid'] ?? null,
                    "The {$name}’s {$source} has a condition rule that still references the outgoing field.",
                );
            }
        }
    }

    private function _createLightswitch(string $name, string $handle): Lightswitch
    {
        $field = new Lightswitch([
            'name' => $name,
            'handle' => $handle,
        ]);
        if (! Craft::$app->getFields()->saveField($field)) {
            throw new RuntimeException("Couldn’t save the $handle field.");
        }

        return $field;
    }

    /**
     * Creates a layout with the outgoing field, followed by a field that’s conditional on it.
     */
    private function _createLayout(string $elementType): FieldLayout
    {
        $layout = new FieldLayout(['type' => $elementType]);
        $outgoingElement = $this->_createLayoutElement($this->outgoingField);
        $conditionalElement = $this->_createLayoutElement($this->conditionalField);
        $conditionalElement->setElementCondition([
            'class' => ElementCondition::class,
            'elementType' => $elementType,
            'conditionRules' => [
                [
                    'class' => LightswitchFieldConditionRule::class,
                    'uid' => StringHelper::UUID(),
                    'fieldUid' => $this->outgoingField->uid,
                    'layoutElementUid' => $outgoingElement->uid,
                    'value' => true,
                ],
            ],
        ]);

        $tab = new FieldLayoutTab([
            'layout' => $layout,
            'name' => 'Content',
        ]);
        $tab->setElements([$outgoingElement, $conditionalElement]);
        $layout->setTabs([$tab]);

        return $layout;
    }

    private function _createLayoutElement(FieldInterface $field): CustomField
    {
        $element = new CustomField($field);
        $element->uid = StringHelper::UUID();

        return $element;
    }
}
