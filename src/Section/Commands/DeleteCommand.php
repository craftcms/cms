<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Support\Facades\Sections as SectionsFacade;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\View\TaskResult;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

final class DeleteCommand extends Command implements PromptsForMissingInput
{
    use ConfirmableTrait;
    use CraftCommand;

    protected $signature = 'craft:sections:delete {sectionHandle : The section handle}';

    protected $description = 'Delete a section';

    protected $aliases = ['sections/delete'];

    public function handle(ProjectConfig $projectConfig, Sections $sections): int
    {
        $this->confirmToProceed(
            warning: 'Project config changes aren’t allowed on this environment.',
            callback: fn () => $projectConfig->readOnly,
        );

        $section = $sections->getSectionByHandle($handle = $this->argument('sectionHandle'));

        if (! $section) {
            $this->components->error("Invalid section handle: $handle");

            return self::FAILURE;
        }

        if ($this->input->isInteractive() && ! confirm("Are you sure you want to delete the section “{$section->name}”?")) {
            $this->components->info('Aborted.');

            return self::SUCCESS;
        }

        $this->components->task(
            description: 'Deleting the section',
            task: function () use ($sections, $section) {
                if (! $sections->deleteSection($section)) {
                    return TaskResult::Failure;
                }

                return TaskResult::Success;
            },
        );

        return self::SUCCESS;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'sectionHandle' => fn () => select(
                label: 'Which section should be deleted?',
                options: SectionsFacade::getAllSections()
                    ->mapWithKeys(fn (Section $section) => [$section->handle => $section->name])
                    ->all(),
            ),
        ];
    }
}
