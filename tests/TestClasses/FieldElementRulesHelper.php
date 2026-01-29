<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses;

use craft\behaviors\CustomFieldBehavior;
use craft\fieldlayoutelements\CustomField;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;

final class FieldElementRulesHelper
{
    /**
     * @return array{0: EntryElement, 1: Field}
     */
    public static function createEntryWithField(
        string $handle,
        string $fieldType,
        array $fieldSettings = [],
        mixed $value = null,
        ?string $scenario = null,
        bool $required = false,
    ): array {
        $field = Field::factory()->create([
            'name' => Str::title($handle),
            'handle' => $handle,
            'type' => $fieldType,
            'settings' => $fieldSettings,
        ]);

        $fieldLayout = FieldLayout::create([
            'type' => EntryElement::class,
            'config' => self::fieldLayoutConfig($field, $required),
        ]);

        $entryModel = EntryModel::factory()->create();
        $entryModel->element->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);
        $entryModel->entryType->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        EntryTypes::refreshEntryTypes();

        CustomFieldBehavior::$fieldHandles[$field->handle] = true;
        Fields::refreshFields();

        /** @var EntryElement $entry */
        $entry = EntryElement::find()->id($entryModel->id)->one();
        $entry->setScenario($scenario ?? Element::SCENARIO_DEFAULT);
        $entry->title = $entry->title ?: 'Test entry';
        $entry->setFieldValue($handle, $value);

        return [$entry, $field];
    }

    public static function fieldLayoutConfig(Field $field, bool $required = false): array
    {
        return [
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Content',
                    'elements' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'type' => CustomField::class,
                            'fieldUid' => $field->uid,
                            'required' => $required,
                        ],
                    ],
                ],
            ],
        ];
    }
}
