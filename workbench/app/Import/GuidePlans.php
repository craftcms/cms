<?php

declare(strict_types=1);

namespace Workbench\App\Import;

use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * The workbench's import plans, which all import into the Guides section: the editable ones the
 * seeder saves, and two registered in code that can be run but not edited.
 */
final class GuidePlans
{
    private const string BASIC_SOURCE = '@craftcms/workbench/resources/import/guides-basic.json';

    private const string COMPLETE_SOURCE = '@craftcms/workbench/resources/import/guides-complete.json';

    private const string COMPLETE_SLOW_SOURCE = '@craftcms/workbench/resources/import/guides-complete-slow.json';

    private const string ERRORS_SOURCE = '@craftcms/workbench/resources/import/guides-with-errors.json';

    private const string SLOW_SOURCE = '@craftcms/workbench/resources/import/slow-run.json';

    private const string SLOW_ERRORS_SOURCE = '@craftcms/workbench/resources/import/slow-run-errors.json';

    /**
     * The editable plans, as the seeder saves them.
     *
     * @return list<ImportPlan>
     */
    public static function editable(): array
    {
        return [
            self::plan('Guides: bare bones', 'guidesBasic', 'Imports each guide’s title and summary, and nothing else.', [
                self::step(['title' => 'title', 'guideSummary' => 'summary'], ['matchCriteria' => ['title' => '1']], self::BASIC_SOURCE),
            ]),
            self::plan('Guides: every field', 'guidesComplete', 'Maps every field a guide has, from its dates and authors to its nested Matrix blocks.', [
                self::completeStep(),
            ]),
            // a batch per guide, so the run's progress moves as each one finishes
            self::plan('Guides: every field, slowly', 'guidesCompleteSlow', 'The same as every field, with each guide taking six seconds to import.', [
                self::completeStep(source: self::COMPLETE_SLOW_SOURCE, batchSize: 1),
            ]),
            // batches of 25, so its progress moves steadily across the whole run
            self::plan('Guides: stress test', 'guidesStress', sprintf('%d guides with every field filled in, to see how a long run holds up.', GuideStress::COUNT), [
                self::completeStep(source: GuideStress::SOURCE, batchSize: GuideStress::BATCH_SIZE, map: ['slug' => 'slug']),
            ]),
            self::plan('Guides: with errors', 'guidesWithErrors', 'Ten new guides, some of which fail validation or reference people who don’t exist.', [
                self::step([
                    ...self::ownFields(),
                    'guideContactEmail' => 'contactEmail',
                    'guideTopics' => 'topics',
                    'guideReviewers' => 'reviewers',
                    'guideContent' => self::contentMap(),
                ], [
                    'matchCriteria' => ['slug' => '1'],
                    'fieldSettings' => ['guideReviewers' => ['matchBy' => 'email']],
                ], self::ERRORS_SOURCE),
            ]),
        ];
    }

    /** Registers the plans that live in code, which the control panel shows but can't edit. */
    public static function register(): void
    {
        $plans = app(ImportPlans::class);

        $plans->register('docsGuides', fn () => self::plan('Guides from the docs', 'docsGuides', 'Registered in code: maps every field a guide has, and can be run but not edited.', [
            self::completeStep('docsGuides'),
        ], editable: false));

        // every item takes a moment and is skipped, and some of the second step's fail (see the service provider)
        $plans->register('nightlyGuideSync', fn () => self::plan('Nightly guide sync', 'nightlyGuideSync', 'Registered in code: three steps that take about 30 seconds, import nothing, and have four failures.', [
            self::slowStep(self::SLOW_SOURCE, 'nightlyGuideSync', 0),
            self::slowStep(self::SLOW_ERRORS_SOURCE, 'nightlyGuideSync', 1),
            self::slowStep(self::SLOW_SOURCE, 'nightlyGuideSync', 2),
        ], editable: false));
    }

    /** @param list<array<string, mixed>> $steps */
    private static function plan(string $name, string $handle, string $description, array $steps, bool $editable = true): ImportPlan
    {
        return new ImportPlan(['editable' => $editable])
            ->name($name)
            ->handle($handle)
            ->description($description)
            ->steps($steps);
    }

    /**
     * @param  array<string, mixed>  $map  Mappings in place of the usual ones.
     * @return array<string, mixed>
     */
    private static function completeStep(?string $registeredHandle = null, string $source = self::COMPLETE_SOURCE, ?int $batchSize = null, array $map = []): array
    {
        return self::step([
            ...self::ownFields(),
            'postDate' => 'postDate',
            'expiryDate' => 'expiryDate',
            'enabled' => 'enabled',
            'authorIds' => 'authors',
            'guideTopics' => 'topics',
            'guideReviewedOn' => 'reviewedOn',
            'guideContactEmail' => 'contactEmail',
            'guideReviewers' => 'reviewers',
            'guideRelated' => 'related',
            'guideContent' => self::contentMap(),
            'guideSidebar' => [
                'guideCallout' => ['fields' => ['blockTone' => 'sidebar.tone', 'blockBody' => 'sidebar.body']],
                'guideQuote' => ['fields' => ['blockQuote' => 'sidebar.quote', 'blockAttribution' => 'sidebar.attribution']],
            ],
            'guideFaq' => [
                'guideFaqItem' => ['title' => 'faq.question', 'fields' => ['blockBody' => 'faq.answer']],
            ],
            ...$map,
        ], [
            'matchCriteria' => ['slug' => '1'],
            'fieldSettings' => [
                'authorIds' => ['matchBy' => 'username'],
                'guideReviewers' => ['matchBy' => 'email'],
                'guideRelated' => ['matchBy' => 'slug'],
            ],
        ], $source, $batchSize, $registeredHandle ? self::uid($registeredHandle, 0) : null);
    }

    /** @return array<string, mixed> */
    private static function slowStep(string $source, string $handle, int $index): array
    {
        return self::step(['title' => 'title', 'guideSummary' => 'summary'], source: $source, batchSize: 10, uid: self::uid($handle, $index));
    }

    /**
     * A guide's own fields, with its slug made from its title.
     *
     * @return array<string, mixed>
     */
    private static function ownFields(): array
    {
        return [
            'title' => 'title',
            'slug' => ImportHelper::TEMPLATE_RULE,
            'guideSummary' => 'summary',
            'guideDifficulty' => 'difficulty',
            'guideFeatured' => 'featured',
            'guideReadingTime' => 'readingTime',
        ];
    }

    /**
     * The Content field's blocks, down to the tips on a step group's steps.
     *
     * @return array<string, mixed>
     */
    private static function contentMap(): array
    {
        return [
            'guideText' => ['fields' => ['blockHeading' => 'content.heading', 'blockBody' => 'content.body']],
            'guideCallout' => ['fields' => ['blockTone' => 'content.tone', 'blockBody' => 'content.body']],
            'guideQuote' => ['fields' => ['blockQuote' => 'content.quote', 'blockAttribution' => 'content.attribution']],
            'guideCode' => ['title' => 'content.title', 'fields' => ['blockLanguage' => 'content.language', 'blockCode' => 'content.code']],
            'guideStepGroup' => ['title' => 'content.title', 'fields' => [
                'blockBody' => 'content.body',
                'guideSteps' => [
                    'guideStep' => ['title' => 'content.steps.title', 'fields' => [
                        'blockBody' => 'content.steps.body',
                        'blockMinutes' => 'content.steps.minutes',
                        'blockOptional' => 'content.steps.optional',
                        'guideStepTips' => [
                            'guideTip' => ['fields' => ['blockBody' => 'content.steps.tips.body', 'blockTone' => 'content.steps.tips.tone']],
                        ],
                    ]],
                    'guideCallout' => ['fields' => ['blockTone' => 'content.steps.tone', 'blockBody' => 'content.steps.body']],
                ],
            ]],
        ];
    }

    /**
     * A step importing into the Guides section.
     *
     * @param  array<string, mixed>  $map
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private static function step(array $map, array $settings = [], string $source = self::BASIC_SOURCE, ?int $batchSize = null, ?string $uid = null): array
    {
        return [
            'uid' => $uid ?? Uuid::uuid4()->toString(),
            'type' => EntryImporter::class,
            'source' => $source,
            'transformer' => null,
            'batchSize' => $batchSize,
            'enabled' => true,
            'settings' => [
                'site' => Sites::getPrimarySite()->handle,
                'section' => 'guides',
                'entryType' => 'guide',
                'map' => $map,
                'templates' => ['slug' => '{title|kebab}'],
                ...$settings,
                // Guides needs an author, and a queued run has no one signed in to be it
                'defaultValues' => ['authorIds' => [self::author()->id]],
            ],
        ];
    }

    /** A registered plan's step keeps its UID from one request to the next, since its runs are recorded against it. */
    private static function uid(string $handle, int $index): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, "workbench-import/$handle/$index")->toString();
    }

    private static function author(): User
    {
        return User::find()->admin()->status(null)->one() ?? throw new RuntimeException('There’s no admin to author the guides.');
    }
}
