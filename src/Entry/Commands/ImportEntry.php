<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Commands;

use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Import\Commands\Import;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Facades\Sections;
use Override;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\select;

class ImportEntry extends Import
{
    #[Override]
    protected $name = 'craft:import:entry';

    #[Override]
    protected $description = 'Imports Craft CMS Entries';

    #[Override]
    protected $aliases = ['import/entry'];

    #[Override]
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('section', null, InputOption::VALUE_OPTIONAL, 'The section to import into.')
            ->addOption('entryType', null, InputOption::VALUE_OPTIONAL, 'The entry type to import into.');
    }

    #[Override]
    public static function importerClass(): string
    {
        return EntryImporter::class;
    }

    #[Override]
    protected function getAdditionalOptions(): array
    {
        return array_merge(parent::getAdditionalOptions(), [
            'section' => [
                'prompt' => fn () => select(
                    label: 'Which section do you want to import into?',
                    options: Sections::getAllSections()->mapWithKeys(fn (Section $section) => [$section->uid => $section->name])->all(),
                ),
            ],
            'entryType' => [
                'prompt' => fn ($responses) => select(
                    label: 'Which entry type do you want to import into?',
                    options: $this->entryTypeOptions($this->option('section') ?? $responses['section']),
                ),
            ],
        ]);
    }

    private function entryTypeOptions(?string $section): array
    {
        if ($section === null) {
            return [];
        }

        $section = Sections::getSectionByUid($section) ?? Sections::getSectionByHandle($section);

        return collect($section?->getEntryTypes())
            ->mapWithKeys(fn ($entryType) => [$entryType->uid => $entryType->name])
            ->all();
    }
}
