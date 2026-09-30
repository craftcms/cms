<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Seeder;
use RuntimeException;

class MatrixPlaygroundSeeder extends Seeder
{
    public function run(): void
    {
        $text = $this->field('matrixText', 'Text', PlainText::class, [
            'multiline' => true,
            'initialRows' => 3,
        ]);
        $tone = $this->field('matrixTone', 'Tone', Dropdown::class, [
            'options' => [
                ['label' => 'Information', 'value' => 'information', 'default' => true],
                ['label' => 'Tip', 'value' => 'tip'],
                ['label' => 'Warning', 'value' => 'warning'],
            ],
        ]);
        $textType = $this->entryType('matrixTextBlock', 'Text block', [
            $this->tab('Content', [$text]),
        ]);
        $calloutType = $this->entryType('matrixCallout', 'Callout', [
            $this->tab('Content', [$text, $tone]),
        ]);

        $tabs = [];
        $matrixFields = [];
        foreach ([
            'matrixBlocks' => ['Blocks', Matrix::VIEW_MODE_BLOCKS, false],
            'matrixCards' => ['Cards', Matrix::VIEW_MODE_CARDS, false],
            'matrixCardGrid' => ['Card grid', Matrix::VIEW_MODE_CARDS_GRID, false],
            'matrixIndex' => ['Index', Matrix::VIEW_MODE_INDEX, true],
            'matrixIndexCards' => ['Index cards only', Matrix::VIEW_MODE_INDEX, false],
        ] as $handle => [$name, $viewMode, $includeTableView]) {
            $matrixFields[] = $field = $this->field($handle, $name, Matrix::class, [
                'entryTypes' => [$textType->id, $calloutType->id],
                'viewMode' => $viewMode,
                'includeTableView' => $includeTableView,
                'defaultIndexViewMode' => $includeTableView ? 'table' : 'cards',
                'defaultTableColumns' => ['type', "field:{$text->uid}", 'dateUpdated'],
                'pageSize' => $viewMode === Matrix::VIEW_MODE_INDEX ? 5 : null,
                'maxEntries' => 16,
                'enableVersioning' => true,
                'createButtonLabel' => 'Add content',
            ]);
            $tabs[] = $this->tab($name, [$field], $tabs === []);
        }

        $ownerType = $this->entryType('matrixPlayground', 'Matrix playground', $tabs);
        $site = Sites::getCurrentSite();
        $section = Sections::getSectionByHandle('matrixPlayground');
        if (! $section) {
            $section = new Section([
                'name' => 'Matrix Playground',
                'handle' => 'matrixPlayground',
                'type' => SectionType::Channel,
                'entryTypes' => [$ownerType],
                'siteSettings' => [
                    $site->id => new SectionSiteSettings([
                        'siteId' => $site->id,
                        'hasUrls' => false,
                    ]),
                ],
            ]);

            if (! Sections::saveSection($section)) {
                throw new RuntimeException('Failed to create the Matrix Playground section.');
            }
        }

        foreach ([
            'Populated views' => 12,
            'Empty views' => 0,
            'At capacity' => 16,
        ] as $title => $count) {
            $slug = Str::slug($title);
            if (Entry::find()->sectionId($section->id)->slug($slug)->status(null)->one()) {
                continue;
            }

            $entry = new Entry([
                'siteId' => $site->id,
                'sectionId' => $section->id,
                'typeId' => $ownerType->id,
                'title' => $title,
                'slug' => $slug,
                'postDate' => now()->subDay(),
            ]);

            foreach ($matrixFields as $field) {
                $entries = $sortOrder = [];
                for ($index = 1; $index <= $count; $index++) {
                    $uid = 'uid:'.Str::uuid();
                    $isCallout = $index % 3 === 0;
                    $enabled = $index % 6 !== 0;
                    $entries[$uid] = [
                        'type' => $isCallout ? $calloutType->handle : $textType->handle,
                        'title' => sprintf('%02d %s%s', $index, $isCallout ? 'Callout' : 'Text block', $enabled ? '' : ' (disabled)'),
                        'enabled' => $enabled,
                        'fields' => [
                            'matrixText' => "Sample {$index} in {$field->name}. Edit this text, duplicate or copy this entry, and reorder it.",
                            ...($isCallout ? ['matrixTone' => $index % 2 === 0 ? 'warning' : 'tip'] : []),
                        ],
                    ];
                    $sortOrder[] = $uid;
                }

                $entry->setFieldValueFromRequest($field->handle, [
                    'entries' => $entries,
                    'sortOrder' => $sortOrder,
                ]);
            }

            if (! Elements::saveElement($entry)) {
                throw new RuntimeException("Failed to create {$title}: ".Json::encode($entry->errors()->all()));
            }
        }
    }

    /** @param class-string<FieldInterface> $type
     * @param  array<string, mixed>  $settings
     */
    private function field(string $handle, string $name, string $type, array $settings): FieldInterface
    {
        if ($field = Fields::getFieldByHandle($handle)) {
            return $field;
        }

        $field = Fields::createField([
            'type' => $type,
            'name' => $name,
            'handle' => $handle,
            'searchable' => true,
            'settings' => $settings,
        ]);

        if (! Fields::saveField($field)) {
            throw new RuntimeException("Failed to create the {$name} field.");
        }

        Fields::refreshFields();

        return $field;
    }

    /** @param list<array<string, mixed>> $tabs */
    private function entryType(string $handle, string $name, array $tabs): EntryType
    {
        if ($entryType = EntryTypes::getEntryTypeByHandle($handle)) {
            return $entryType;
        }

        $layout = FieldLayout::create([
            'uid' => Str::uuid()->toString(),
            'type' => Entry::class,
            'config' => ['tabs' => $tabs],
        ]);
        $entryType = new EntryType([
            'name' => $name,
            'handle' => $handle,
            'fieldLayoutId' => $layout->id,
        ]);

        if (! EntryTypes::saveEntryType($entryType)) {
            throw new RuntimeException("Failed to create the {$name} entry type.");
        }

        return $entryType;
    }

    /**
     * @param  list<FieldInterface>  $fields
     * @return array<string, mixed>
     */
    private function tab(string $name, array $fields, bool $includeTitle = true): array
    {
        return [
            'uid' => Str::uuid()->toString(),
            'name' => $name,
            'elements' => [
                ...($includeTitle ? [[
                    'uid' => Str::uuid()->toString(),
                    'type' => EntryTitleField::class,
                    'required' => true,
                ]] : []),
                ...array_map(fn (FieldInterface $field): array => [
                    'uid' => Str::uuid()->toString(),
                    'type' => CustomField::class,
                    'fieldUid' => $field->uid,
                ], $fields),
            ],
        ];
    }
}
