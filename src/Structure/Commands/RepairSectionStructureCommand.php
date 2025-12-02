<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Sections;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\select;

final class RepairSectionStructureCommand extends RepairCommand implements PromptsForMissingInput
{
    use CraftCommand;

    protected $signature = 'craft:utils:repair:section-structure {handle} {--dry-run}';

    protected $description = 'Repairs structure data for a section.';

    protected $aliases = ['utils/repair/section-structure', 'repair:section-structure', 'repair/section-structure'];

    public function handle(Sections $sections): int
    {
        $section = $sections->getSectionByHandle($handle = $this->argument('handle'));

        if (! $section) {
            $this->components->error("Invalid section handle: $handle");

            return self::FAILURE;
        }

        if ($section->type !== SectionType::Structure) {
            $this->components->error("$section->name is not a Structure section");

            return self::FAILURE;
        }

        return $this->repairStructure($section->structureId, Entry::find()->section($section));
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'handle' => fn () => select(
                label: 'Select a section',
                options: resolve(Sections::class)->getAllSections()
                    ->filter(fn (Section $section) => $section->type === SectionType::Structure)
                    ->mapWithKeys(fn (Section $section) => [$section->handle => $section->name])
                    ->all(),
            ),
        ];
    }
}
