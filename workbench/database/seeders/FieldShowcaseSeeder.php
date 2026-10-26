<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Country;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Markdown;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * A channel whose entry type has one of every field type, spread over several
 * tabs, with a front-end template that renders them all.
 */
class FieldShowcaseSeeder extends Seeder
{
    private const array OPTIONS = [
        ['label' => 'Red', 'value' => 'red', 'default' => true],
        ['label' => 'Green', 'value' => 'green'],
        ['label' => 'Blue', 'value' => 'blue'],
    ];

    public function run(): void
    {
        $tabs = [
            $this->tab('Text', [
                $this->field('showcasePlainText', 'Plain Text', PlainText::class),
                $this->field('showcaseMultiline', 'Multi-line Text', PlainText::class, ['multiline' => true, 'initialRows' => 3]),
                $this->field('showcaseMarkdown', 'Markdown', Markdown::class, ['initialRows' => 6]),
                $this->field('showcaseEmail', 'Email', Email::class),
                $this->field('showcaseLink', 'Link', Link::class, [
                    'types' => ['url', 'entry', 'email', 'tel'],
                    'showLabelField' => true,
                ]),
            ], includeTitle: true),
            $this->tab('Numbers & Dates', [
                $this->field('showcaseNumber', 'Number', Number::class, ['decimals' => 1, 'suffix' => 'kg']),
                $this->field('showcaseMoney', 'Money', Money::class, ['currency' => 'USD']),
                $this->field('showcaseRange', 'Range', Range::class, ['min' => 0, 'max' => 10, 'suffix' => '/ 10']),
                $this->field('showcaseDate', 'Date', Date::class, ['showTime' => true]),
                $this->field('showcaseTime', 'Time', Time::class),
                $this->field('showcaseColor', 'Color', Color::class, [
                    'palette' => [
                        ['color' => '#e5422b', 'label' => 'Craft red'],
                        ['color' => '#2563eb', 'label' => 'Blue'],
                    ],
                    'allowCustomColors' => true,
                ]),
            ]),
            $this->tab('Options', [
                $this->field('showcaseLightswitch', 'Lightswitch', Lightswitch::class, ['onLabel' => 'On', 'offLabel' => 'Off']),
                $this->field('showcaseDropdown', 'Dropdown', Dropdown::class, ['options' => self::OPTIONS]),
                $this->field('showcaseRadio', 'Radio Buttons', RadioButtons::class, ['options' => self::OPTIONS]),
                $this->field('showcaseCheckboxes', 'Checkboxes', Checkboxes::class, ['options' => self::OPTIONS]),
                $this->field('showcaseMultiSelect', 'Multi-select', MultiSelect::class, ['options' => self::OPTIONS]),
                $this->field('showcaseButtonGroup', 'Button Group', ButtonGroup::class, ['options' => self::OPTIONS]),
                $this->field('showcaseCountry', 'Country', Country::class),
                $this->field('showcaseIcon', 'Icon', Icon::class),
            ]),
            $this->tab('Relations', [
                $this->field('showcaseEntries', 'Entries', Entries::class, ['sources' => '*']),
                $this->field('showcaseAssets', 'Assets', Assets::class, ['sources' => '*']),
                $this->field('showcaseUsers', 'Users', Users::class, ['sources' => '*']),
            ]),
            $this->tab('Structured', [
                $this->field('showcaseTable', 'Table', Table::class, [
                    'columns' => [
                        'col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline'],
                        'col2' => ['heading' => 'Quantity', 'handle' => 'quantity', 'type' => 'number'],
                    ],
                    'defaults' => [],
                ]),
                $this->field('showcaseContentBlock', 'Content Block', ContentBlock::class, [
                    'fieldLayouts' => [
                        Str::uuid()->toString() => ['tabs' => [
                            $this->tab('Content', [
                                $this->field('showcaseBlockHeading', 'Heading', PlainText::class),
                                $this->field('showcaseBlockText', 'Text', PlainText::class, ['multiline' => true]),
                            ]),
                        ]],
                    ],
                ]),
                $this->field('showcaseMatrix', 'Matrix', Matrix::class, [
                    'entryTypes' => array_values(array_filter([
                        EntryTypes::getEntryTypeByHandle('matrixTextBlock')?->id,
                        EntryTypes::getEntryTypeByHandle('matrixCallout')?->id,
                    ])),
                    'viewMode' => Matrix::VIEW_MODE_BLOCKS,
                    'createButtonLabel' => 'Add block',
                ]),
                $this->field('showcaseAddresses', 'Addresses', Addresses::class),
                $this->field('showcaseJson', 'JSON', Json::class),
            ]),
        ];

        $entryType = $this->entryType('showcase', 'Showcase', $tabs);
        $section = $this->section($entryType);

        $this->entry($section, $entryType, 'Kitchen sink', $this->sampleValues());
        $this->entry($section, $entryType, 'Blank slate', []);
    }

    /** @return array<string, mixed> */
    private function sampleValues(): array
    {
        $posts = Entry::find()->section('posts')->limit(2)->ids();
        $image = $this->image();

        return [
            'showcasePlainText' => 'A single line of text',
            'showcaseMultiline' => "First line\nSecond line",
            'showcaseMarkdown' => "Some **bold** and _italic_ text.\n\n- One\n- Two\n- Three",
            'showcaseEmail' => 'hello@example.com',
            'showcaseLink' => ['type' => 'url', 'value' => 'https://craftcms.com', 'label' => 'Craft CMS'],
            'showcaseNumber' => 42.5,
            'showcaseMoney' => ['value' => '1999', 'currency' => 'USD'],
            'showcaseRange' => 7,
            'showcaseDate' => now()->addWeek()->setTime(14, 30),
            'showcaseTime' => ['time' => '09:15'],
            'showcaseColor' => '#e5422b',
            'showcaseLightswitch' => true,
            'showcaseDropdown' => 'green',
            'showcaseRadio' => 'blue',
            'showcaseCheckboxes' => ['red', 'blue'],
            'showcaseMultiSelect' => ['green'],
            'showcaseButtonGroup' => 'red',
            'showcaseCountry' => 'NL',
            'showcaseIcon' => 'rocket',
            'showcaseEntries' => $posts,
            'showcaseAssets' => $image ? [$image->id] : [],
            'showcaseUsers' => [1],
            'showcaseTable' => [
                ['col1' => 'Apples', 'col2' => 3],
                ['col1' => 'Pears', 'col2' => 5],
            ],
            'showcaseContentBlock' => ['fields' => [
                'showcaseBlockHeading' => 'Inside a content block',
                'showcaseBlockText' => 'Nested fields render here.',
            ]],
            'showcaseMatrix' => [
                'entries' => [
                    'new1' => ['type' => 'matrixTextBlock', 'title' => 'First block', 'fields' => ['matrixText' => 'A text block.']],
                    'new2' => ['type' => 'matrixCallout', 'title' => 'Second block', 'fields' => ['matrixText' => 'A callout.', 'matrixTone' => 'tip']],
                ],
                'sortOrder' => ['new1', 'new2'],
            ],
            'showcaseAddresses' => [
                'entries' => [
                    'new1' => [
                        'title' => 'Office',
                        'countryCode' => 'US',
                        'addressLine1' => '1 Main Street',
                        'locality' => 'Portland',
                        'administrativeArea' => 'OR',
                        'postalCode' => '97201',
                    ],
                ],
                'sortOrder' => ['new1'],
            ],
            'showcaseJson' => '{"name": "Craft", "version": 6, "tags": ["cms", "laravel"]}',
        ];
    }

    /** A generated image in the Images volume, so the Assets field has something to show. */
    private function image(): ?Asset
    {
        $volume = Volumes::getVolumeByHandle('images');
        if (! $volume || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        if ($asset = Asset::find()->volumeId($volume->id)->filename('showcase.png')->one()) {
            return $asset;
        }

        $path = Path::temp('showcase.png');
        $image = imagecreatetruecolor(800, 450);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 229, 66, 43));
        imagefilledellipse($image, 400, 225, 300, 300, (int) imagecolorallocate($image, 255, 255, 255));
        imagepng($image, $path);

        $asset = new Asset([
            'volumeId' => $volume->id,
            'newFolderId' => app(Folders::class)->getRootFolderByVolumeId($volume->id)?->id,
            'tempFilePath' => $path,
            'newFilename' => 'showcase.png',
            'title' => 'Showcase image',
        ]);
        $asset->ruleset->useScenario(AssetRules::SCENARIO_CREATE);

        if (! Elements::saveElement($asset)) {
            throw new RuntimeException('Failed to create the showcase image: '.JsonHelper::encode($asset->errors()->all()));
        }

        return $asset;
    }

    private function section(EntryType $entryType): Section
    {
        if ($section = Sections::getSectionByHandle('showcase')) {
            return $section;
        }

        $site = Sites::getCurrentSite();
        $section = new Section([
            'name' => 'Field Showcase',
            'handle' => 'showcase',
            'type' => SectionType::Channel,
            'entryTypes' => [$entryType],
            'siteSettings' => [
                $site->id => new SectionSiteSettings([
                    'siteId' => $site->id,
                    'hasUrls' => true,
                    'uriFormat' => 'showcase/{slug}',
                    'template' => '_showcase/entry',
                ]),
            ],
        ]);

        if (! Sections::saveSection($section)) {
            throw new RuntimeException('Failed to create the Field Showcase section.');
        }

        return $section;
    }

    /** @param array<string, mixed> $values */
    private function entry(Section $section, EntryType $entryType, string $title, array $values): void
    {
        $slug = Str::slug($title);
        if (Entry::find()->sectionId($section->id)->slug($slug)->status(null)->one()) {
            return;
        }

        $entry = new Entry([
            'siteId' => Sites::getCurrentSite()->id,
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
            'title' => $title,
            'slug' => $slug,
            'postDate' => now()->subDay(),
        ]);

        foreach ($values as $handle => $value) {
            $entry->setFieldValueFromRequest($handle, $value);
        }

        if (! Elements::saveElement($entry)) {
            throw new RuntimeException("Failed to create {$title}: ".JsonHelper::encode($entry->errors()->all()));
        }
    }

    /**
     * @param  class-string<FieldInterface>  $type
     * @param  array<string, mixed>  $settings
     */
    private function field(string $handle, string $name, string $type, array $settings = []): FieldInterface
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
            throw new RuntimeException("Failed to create the {$name} field: ".JsonHelper::encode($field->errors()->all()));
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
    private function tab(string $name, array $fields, bool $includeTitle = false): array
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
