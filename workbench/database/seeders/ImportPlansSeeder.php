<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Import plans into the Guides section that map more and more deeply nested content, from
 * flat fields to Matrix blocks three levels down, all from the same data file.
 */
class ImportPlansSeeder extends Seeder
{
    private const string SOURCE = '@craftcms/workbench/resources/import/guides.json';

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
    }

    /**
     * @param  array<string, mixed>  $map
     * @param  array<string, mixed>  $settings
     */
    private function plan(string $name, string $handle, string $description, array $map, array $settings = []): void
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
                'source' => self::SOURCE,
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
