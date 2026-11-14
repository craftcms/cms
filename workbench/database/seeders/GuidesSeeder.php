<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
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
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * A Guides section whose content nests Matrix fields three levels deep, for exercising imports
 * against `resources/complex-guides.json`.
 */
class GuidesSeeder extends Seeder
{
    public function run(): void
    {
        $heading = $this->field('blockHeading', 'Heading', PlainText::class, []);
        $body = $this->field('blockBody', 'Body', PlainText::class, ['multiline' => true, 'initialRows' => 3]);
        $tone = $this->field('blockTone', 'Tone', Dropdown::class, [
            'options' => [
                ['label' => 'Information', 'value' => 'information', 'default' => true],
                ['label' => 'Tip', 'value' => 'tip'],
                ['label' => 'Warning', 'value' => 'warning'],
            ],
        ]);
        $quote = $this->field('blockQuote', 'Quote', PlainText::class, ['multiline' => true]);
        $attribution = $this->field('blockAttribution', 'Attribution', PlainText::class, []);
        $language = $this->field('blockLanguage', 'Language', Dropdown::class, [
            'options' => [
                ['label' => 'Twig', 'value' => 'twig', 'default' => true],
                ['label' => 'PHP', 'value' => 'php'],
                ['label' => 'JavaScript', 'value' => 'js'],
                ['label' => 'Shell', 'value' => 'shell'],
            ],
        ]);
        $code = $this->field('blockCode', 'Code', PlainText::class, ['multiline' => true, 'code' => true]);
        $minutes = $this->field('blockMinutes', 'Minutes', Number::class, ['suffix' => 'min']);
        $optional = $this->field('blockOptional', 'Optional', Lightswitch::class, []);

        $tipType = $this->entryType('guideTip', 'Tip', [$this->tab('Content', [$body, $tone], false)]);
        $stepTips = $this->matrix('guideStepTips', 'Tips', [$tipType]);
        $calloutType = $this->entryType('guideCallout', 'Callout', [$this->tab('Content', [$tone, $body], false)]);
        $stepType = $this->entryType('guideStep', 'Step', [$this->tab('Content', [$body, $minutes, $optional, $stepTips])]);
        $steps = $this->matrix('guideSteps', 'Steps', [$stepType, $calloutType]);

        $textType = $this->entryType('guideText', 'Text', [$this->tab('Content', [$heading, $body], false)]);
        $quoteType = $this->entryType('guideQuote', 'Quote', [$this->tab('Content', [$quote, $attribution], false)]);
        $codeType = $this->entryType('guideCode', 'Code sample', [$this->tab('Content', [$language, $code])]);
        $stepGroupType = $this->entryType('guideStepGroup', 'Step group', [$this->tab('Content', [$body, $steps])]);
        $faqType = $this->entryType('guideFaqItem', 'Question', [$this->tab('Content', [$body])]);

        $content = $this->matrix('guideContent', 'Content', [$textType, $calloutType, $quoteType, $codeType, $stepGroupType]);
        $sidebar = $this->matrix('guideSidebar', 'Sidebar', [$calloutType, $quoteType]);
        $faq = $this->matrix('guideFaq', 'FAQ', [$faqType]);

        $summary = $this->field('guideSummary', 'Summary', PlainText::class, ['multiline' => true]);
        $difficulty = $this->field('guideDifficulty', 'Difficulty', Dropdown::class, [
            'options' => [
                ['label' => 'Beginner', 'value' => 'beginner', 'default' => true],
                ['label' => 'Intermediate', 'value' => 'intermediate'],
                ['label' => 'Advanced', 'value' => 'advanced'],
            ],
        ]);
        $featured = $this->field('guideFeatured', 'Featured', Lightswitch::class, []);
        $readingTime = $this->field('guideReadingTime', 'Reading Time', Number::class, ['suffix' => 'min']);
        $topics = $this->field('guideTopics', 'Topics', Checkboxes::class, [
            'options' => [
                ['label' => 'Content modeling', 'value' => 'content-modeling'],
                ['label' => 'Templating', 'value' => 'templating'],
                ['label' => 'Importing', 'value' => 'importing'],
                ['label' => 'Performance', 'value' => 'performance'],
                ['label' => 'Plugins', 'value' => 'plugins'],
            ],
        ]);
        $reviewedOn = $this->field('guideReviewedOn', 'Reviewed On', Date::class, []);
        $contactEmail = $this->field('guideContactEmail', 'Contact Email', Email::class, []);
        $reviewers = $this->field('guideReviewers', 'Reviewers', Users::class, ['sources' => '*']);
        $related = $this->field('guideRelated', 'Related Guides', Entries::class, ['sources' => '*']);

        $guideType = $this->entryType('guide', 'Guide', [
            $this->tab('Overview', [$summary, $difficulty, $featured, $readingTime, $topics, $reviewedOn, $contactEmail]),
            $this->tab('Content', [$content], false),
            $this->tab('Extras', [$sidebar, $faq], false),
            $this->tab('Relations', [$reviewers, $related], false),
        ]);

        $site = Sites::getCurrentSite();
        $section = Sections::getSectionByHandle('guides');
        if (! $section) {
            $section = new Section([
                'name' => 'Guides',
                'handle' => 'guides',
                'type' => SectionType::Channel,
                'entryTypes' => [$guideType],
                'siteSettings' => [
                    $site->id => new SectionSiteSettings([
                        'siteId' => $site->id,
                        'hasUrls' => false,
                    ]),
                ],
            ]);

            if (! Sections::saveSection($section)) {
                throw new RuntimeException('Failed to create the Guides section.');
            }
        }

        // the import file relates to these by slug and updates the first one
        foreach ([
            'getting-started-with-craft' => ['Getting started with Craft', 'beginner'],
            'content-modeling-basics' => ['Content modeling basics', 'beginner'],
        ] as $slug => [$title, $level]) {
            if (Entry::find()->sectionId($section->id)->slug($slug)->status(null)->one()) {
                continue;
            }

            $entry = new Entry([
                'siteId' => $site->id,
                'sectionId' => $section->id,
                'typeId' => $guideType->id,
                'title' => $title,
                'slug' => $slug,
                'postDate' => now()->subWeek(),
            ]);
            $entry->setFieldValues([
                'guideSummary' => "The seeded version of “{$title}”, before any import.",
                'guideDifficulty' => $level,
            ]);
            $entry->setFieldValueFromRequest('guideContent', [
                'entries' => [
                    'new:1' => [
                        'type' => $textType->handle,
                        'fields' => ['blockHeading' => 'Seeded text', 'blockBody' => 'This block was here before the import.'],
                    ],
                ],
                'sortOrder' => ['new:1'],
            ]);

            if (! Elements::saveElement($entry)) {
                throw new RuntimeException("Failed to create {$title}: ".Json::encode($entry->errors()->all()));
            }
        }
    }

    /** @param list<EntryType> $entryTypes */
    private function matrix(string $handle, string $name, array $entryTypes): FieldInterface
    {
        return $this->field($handle, $name, Matrix::class, [
            'entryTypes' => array_map(fn (EntryType $entryType) => $entryType->id, $entryTypes),
            'viewMode' => Matrix::VIEW_MODE_BLOCKS,
            'createButtonLabel' => "Add {$name}",
        ]);
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
            throw new RuntimeException("Failed to create the {$name} field: ".Json::encode($field->errors()->all()));
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
        $hasTitle = false;
        foreach ($tabs as $tab) {
            foreach ($tab['elements'] as $element) {
                $hasTitle = $hasTitle || $element['type'] === EntryTitleField::class;
            }
        }
        $entryType = new EntryType([
            'name' => $name,
            'handle' => $handle,
            'fieldLayoutId' => $layout->id,
            'hasTitleField' => $hasTitle,
            ...($hasTitle ? [] : ['titleFormat' => '{type.name}']),
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
