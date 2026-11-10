<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Extensions\TextTwigExtension;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Import plans into the Guides section that map more and more deeply nested content, from
 * flat fields to Matrix blocks three levels down, all from the same data file, and one with
 * enough items, and existing guides, to preview every result an item can have.
 */
class ImportPlansSeeder extends Seeder
{
    private const string SOURCE = '@craftcms/workbench/resources/import/guides.json';

    private const string PREVIEW_SOURCE = '@craftcms/workbench/resources/import/guides-preview.json';

    /**
     * The guides the preview data matches: the first six as the data has them, the rest with older summaries.
     *
     * @var list<array{string, string, string, bool, int}>
     */
    private const array PREVIEW_EXISTING = [
        ['Choosing a hosting plan', 'How to approach choosing a hosting plan.', 'beginner', true, 5],
        ['Setting up environments', 'How to approach setting up environments.', 'intermediate', false, 12],
        ['Writing your first template', 'How to approach writing your first template.', 'advanced', false, 19],
        ['Working with assets', 'How to approach working with assets.', 'beginner', false, 26],
        ['Managing users and permissions', 'How to approach managing users and permissions.', 'intermediate', true, 8],
        ['Caching strategies', 'How to approach caching strategies.', 'advanced', false, 15],
        ['Building navigation menus', 'How to approach building navigation menus.', 'intermediate', false, 25],
        ['Localizing your site', 'How to approach localizing your site.', 'advanced', false, 7],
        ['Using the GraphQL API', 'How to approach using the GraphQL API.', 'beginner', true, 14],
        ['Planning a migration', 'How to approach planning a migration.', 'intermediate', false, 21],
        ['Configuring email', 'How to approach configuring email.', 'advanced', false, 28],
        ['Running scheduled tasks', 'How to approach running scheduled tasks.', 'beginner', false, 10],
        ['Deploying with zero downtime', 'How to approach deploying with zero downtime.', 'intermediate', true, 17],
        ['Debugging slow pages', 'How to approach debugging slow pages.', 'advanced', false, 24],
    ];

    public function run(): void
    {
        $flat = [
            'title' => 'title',
            'slug' => ImportHelper::TEMPLATE_RULE,
            'guideSummary' => 'summary',
            'guideDifficulty' => 'difficulty',
            'guideFeatured' => 'featured',
            'guideReadingTime' => 'readingTime',
        ];

        $oneLevel = [
            ...$flat,
            'guideContent' => [
                'guideText' => ['fields' => [
                    'blockHeading' => 'content.heading',
                    'blockBody' => 'content.body',
                ]],
                'guideQuote' => ['fields' => [
                    'blockQuote' => 'content.quote',
                    'blockAttribution' => 'content.attribution',
                ]],
            ],
            'guideFaq' => [
                'guideFaqItem' => [
                    'title' => 'faq.question',
                    'fields' => ['blockBody' => 'faq.answer'],
                ],
            ],
        ];

        $threeLevels = $oneLevel;
        $threeLevels['guideContent']['guideStepGroup'] = ['title' => 'content.title', 'fields' => [
            'blockBody' => 'content.body',
            'guideSteps' => [
                'guideStep' => [
                    'title' => 'content.steps.title',
                    'fields' => [
                        'blockBody' => 'content.steps.body',
                        'blockMinutes' => 'content.steps.minutes',
                        'blockOptional' => 'content.steps.optional',
                        'guideStepTips' => [
                            'guideTip' => ['fields' => [
                                'blockBody' => 'content.steps.tips.body',
                                'blockTone' => 'content.steps.tips.tone',
                            ]],
                        ],
                    ],
                ],
                'guideCallout' => ['fields' => [
                    'blockTone' => 'content.steps.tone',
                    'blockBody' => 'content.steps.body',
                ]],
            ],
        ]];

        // the steps' and questions' required titles come from paths the data doesn't have, so their errors show up nested
        $nestedErrors = $threeLevels;
        $nestedErrors['guideContent']['guideStepGroup']['fields']['guideSteps']['guideStep']['title'] = 'content.steps.heading';
        unset($nestedErrors['guideFaq']['guideFaqItem']['title']);

        $this->plan('Guides: flat fields', 'guidesFlat', 'Maps a guide’s own fields, with its slug made from a template.', $flat, [
            'matchCriteria' => ['slug' => '1'],
            'matchMode' => 'updateOnly',
        ]);
        $this->plan('Guides: one level of nesting', 'guidesOneLevel', 'Adds the Content and FAQ Matrix fields’ blocks.', $oneLevel, [
            'matchCriteria' => ['slug' => '1'],
        ]);
        $this->plan('Guides: three levels of nesting', 'guidesThreeLevels', 'Adds step groups, their steps, and each step’s tips.', $threeLevels, [
            'matchCriteria' => ['slug' => '1'],
            'clearableItems' => ['guideReadingTime' => '1'],
            'defaultValues' => ['guideDifficulty' => 'beginner'],
        ]);
        $this->plan('Guides: nested errors', 'guidesNestedErrors', 'Leaves the steps’ and questions’ titles unmapped, to show where nested entries fail validation.', $nestedErrors, [
            'matchCriteria' => ['slug' => '1'],
        ]);

        // the data has items that are new, update or match these guides as they are, are skipped (see the workbench's
        // service provider), or fail validation
        $this->seedPreviewGuides();
        $this->plan('Guides: every preview result', 'guidesPreviewResults', 'Enough guides to page through, with an item for every result a preview can show.', $flat, [
            'matchCriteria' => ['slug' => '1'],
        ], self::PREVIEW_SOURCE);
    }

    private function seedPreviewGuides(): void
    {
        $section = Sections::getSectionByHandle('guides') ?? throw new RuntimeException('The Guides section doesn’t exist.');
        $type = EntryTypes::getEntryTypeByHandle('guide') ?? throw new RuntimeException('The Guide entry type doesn’t exist.');
        $updated = 6;

        foreach (self::PREVIEW_EXISTING as $i => [$title, $summary, $difficulty, $featured, $readingTime]) {
            // as the plan's slug template makes it, for the data's items to match on
            $slug = app(TextTwigExtension::class)->kebabFilter($title);

            if (Entry::find()->sectionId($section->id)->slug($slug)->status(null)->exists()) {
                continue;
            }

            $entry = new Entry([
                'siteId' => Sites::getPrimarySite()->id,
                'sectionId' => $section->id,
                'typeId' => $type->id,
                'title' => $title,
                'slug' => $slug,
                'postDate' => now()->subMonth(),
            ]);
            $entry->setFieldValues([
                'guideSummary' => $i < $updated ? $summary : "The seeded version of “{$title}”, before any import.",
                'guideDifficulty' => $difficulty,
                'guideFeatured' => $featured,
                'guideReadingTime' => $readingTime,
            ]);

            if (! Elements::saveElement($entry)) {
                throw new RuntimeException("Failed to create {$title}: ".Json::encode($entry->errors()->all()));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $map
     * @param  array<string, mixed>  $settings
     */
    private function plan(string $name, string $handle, string $description, array $map, array $settings = [], string $source = self::SOURCE): void
    {
        $plans = app(ImportPlans::class);

        if ($plans->getImportPlanByHandle($handle) !== null) {
            return;
        }

        $plan = new ImportPlan(['editable' => true])
            ->name($name)
            ->handle($handle)
            ->description($description)
            ->steps([[
                'uid' => Str::uuid()->toString(),
                'type' => EntryImporter::class,
                'source' => $source,
                'transformer' => null,
                'batchSize' => null,
                'enabled' => true,
                'settings' => [
                    'site' => Sites::getPrimarySite()->handle,
                    'section' => 'guides',
                    'entryType' => 'guide',
                    'map' => $map,
                    'templates' => ['slug' => '{title|kebab}'],
                    ...$settings,
                ],
            ]]);

        if (! $plans->saveImportPlan($plan)) {
            throw new RuntimeException("Failed to create the {$name} import plan: ".Json::encode($plan->errors()->all()));
        }
    }
}
