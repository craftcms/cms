<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Commands;

use craft\elements\Entry;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\EntryType\Data\EntryType;
use CraftCms\Cms\EntryType\EntryTypes;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\Rules\HandleRule;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\View\TaskResult;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

final class CreateCommand extends Command
{
    use ConfirmableTrait;
    use CraftCommand;

    protected $signature = 'craft:sections:create
        {--name= : The section name.}
        {--handle= : The section handle.}
        {--type= : The section type (single, channel, or structure).}
        {--noVersioning= : Whether to disable versioning for the section.}
        {--entryTypes=* : Comma-separated list of entry type handles to assign to the section.}
        {--uriFormat= : The entry URI format to set for each site.}
        {--template= : The template to load when an entry’s URL is requested.}
    ';

    protected $description = 'Create a new section';

    protected $aliases = ['sections/create'];

    public function handle(ProjectConfig $projectConfig, EntryTypes $entryTypesService): void
    {
        $this->confirmToProceed(
            warning: 'Project config changes aren’t allowed on this environment.',
            callback: fn () => $projectConfig->readOnly,
        );

        $rules = Section::rules();

        $entryTypes = collect($this->option('entryTypes'))
            ->flatMap(fn (string $entryType) => explode(',', $entryType))
            ->map(function (string $entryTypeHandle) use ($entryTypesService) {
                $entryType = $entryTypesService->getEntryTypeByHandle($entryTypeHandle);

                if (! $entryType) {
                    throw new \RuntimeException("Invalid entry type handle: {$entryTypeHandle}");
                }

                return $entryType->handle;
            })
            ->filter()
            ->all();

        $allEntryTypes = $entryTypesService->getAllEntryTypes()->keyBy('handle');
        $saveEntryType = false;

        $responses = form()
            ->text(
                label: 'Section name',
                default: $this->option('name') ?? '',
                required: true,
                validate: ['name' => $rules['name']],
                name: 'name'
            )
            ->add(fn ($responses) => text(
                label: 'Section handle',
                default: $this->option('handle') ?? Str::toHandle($responses['name']),
                required: true,
                validate: ['handle' => $rules['handle']],
            ), 'handle')
            ->select(
                label: 'Section type',
                options: collect(SectionType::cases())
                    ->mapWithKeys(fn (SectionType $type) => [$type->value => $type->descriptiveLabel()])
                    ->all(),
                default: $this->option('type'),
                name: 'type',
            )
            ->confirm(
                label: 'Enable entry versioning for the section?',
                default: ! $this->option('noVersioning'),
                required: true,
                name: 'enableVersioning',
            )
            ->addIf(empty($entryTypes) && $allEntryTypes->isNotEmpty(), fn () => confirm(
                label: 'Have you already created an entry type for this section?'
            ), 'createdEntryType')
            ->addIf(
                condition: fn ($responses) => isset($responses['createdEntryType']) && $responses['createdEntryType'],
                step: fn () => select(
                    label: 'Which entry type should be used?',
                    options: $allEntryTypes->map(
                        fn (EntryType $entryType) => $entryType->name,
                    )->all(),
                ),
                name: 'entryTypeHandle')
            ->addIf(
                condition: fn ($responses) => ! isset($responses['entryTypeHandle']),
                step: function ($responses) use (&$saveEntryType) {
                    $saveEntryType = true;

                    return text(
                        label: 'Entry type name',
                        default: Str::singular($responses['name']),
                        required: true,
                        validate: ['name' => ['string', 'max:255']],
                    );
                },
                name: 'entryTypeName'
            )
            ->addIf(
                condition: fn ($responses) => ! isset($responses['entryTypeHandle']),
                step: fn ($responses) => text(
                    label: 'Entry type handle',
                    default: Str::toHandle($responses['entryTypeName']),
                    required: true,
                    validate: ['handle' => [
                        'string',
                        'max:255',
                        new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
                        Rule::unique(Table::ENTRYTYPES, 'handle'),
                    ]],
                ),
                name: 'entryTypeHandle'
            )
            ->submit();

        $section = new Section(
            name: $responses['name'],
            handle: $responses['handle'],
            type: SectionType::from($responses['type']),
            enableVersioning: $responses['enableVersioning'],
        );

        $section->setSiteSettings(Sites::getAllSites(true)->map(
            fn (Site $site) => new SectionSiteSettings(
                siteId: $site->id,
                hasUrls: $this->option('uriFormat') !== null,
                uriFormat: $this->option('uriFormat'),
                template: $this->option('template'),
            )
        )->all());

        $hasUrls = collect($section->getSiteSettings())->contains(
            fn (SectionSiteSettings $siteSettings) => $siteSettings->hasUrls,
        );

        if ($hasUrls) {
            $section->previewTargets = [
                [
                    'label' => t('Primary {type} page', [
                        'type' => Entry::lowerDisplayName(),
                    ]),
                    'urlFormat' => '{url}',
                ],
            ];
        }

        $entryType = null;
        if ($saveEntryType) {
            $this->components->task(
                description: 'Saving the entry type',
                task: function () use ($entryTypesService, $responses, &$entryType) {
                    $entryType = new EntryType;
                    $entryType->name = $responses['entryTypeName'];
                    $entryType->handle = $responses['entryTypeHandle'];

                    $entryTypesService->saveEntryType($entryType);
                }
            );
        } else {
            $entryType = $entryTypesService->getEntryTypeByHandle($responses['entryTypeHandle']);
        }

        $section->setEntryTypes([$entryType]);

        $this->components->task(
            description: 'Saving the section',
            task: function () use ($section) {
                if (! Sections::saveSection($section)) {
                    return TaskResult::Failure;
                }

                return TaskResult::Success;
            }
        );
    }
}
