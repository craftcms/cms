<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Utils;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Override;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

class PruneRevisionsCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:utils:prune-revisions
        {--section= : The section handle(s) to prune revisions from. Can be set to multiple comma-separated sections.}
        {--max-revisions= : The maximum number of revisions an element can have.}
        {--dry-run : Report extra revisions without deleting them.}
    ';

    #[Override]
    protected $description = 'Prunes excess element revisions.';

    #[Override]
    protected $aliases = ['utils/prune-revisions', 'utils/prune-revisions:index', 'utils/prune-revisions/index'];

    public function handle(Connection $connection, Elements $elementsService, GeneralConfig $generalConfig): int
    {
        $sectionIds = $this->resolveSectionIds();

        if ($sectionIds === null) {
            return self::FAILURE;
        }

        $maxRevisions = $this->maxRevisions($generalConfig);

        $this->components->task(
            'Finding elements with too many revisions',
            function () use ($maxRevisions, $sectionIds, $connection, &$elements) {
                $elements = $this->elementsWithExtraRevisions($connection, $sectionIds, $maxRevisions);
            },
        );

        if ($elements->isEmpty()) {
            $this->components->success('Nothing to prune.');

            return self::SUCCESS;
        }

        $prunedRevisionCount = 0;

        foreach ($elements as $element) {
            if (! class_exists($element->type)) {
                continue;
            }

            /** @var class-string<ElementInterface> $elementType */
            $elementType = $element->type;
            $extraRevisionCount = $element->count - $maxRevisions;
            $extraRevisions = $elementType::find()
                ->revisionOf($element->id)
                ->site('*')
                ->unique()
                ->status(null)
                ->orderByDesc('num')
                ->offset($maxRevisions)
                ->all();

            $this->components->twoColumnDetail(
                $this->taskLabel($elementType::displayName(), $element->id, $extraRevisionCount),
                '<fg=yellow;options=bold>MATCHED</>',
            );

            if (count($extraRevisions) !== $extraRevisionCount) {
                $this->components->warn("Expected to find $extraRevisionCount ".Str::plural('revision', $extraRevisionCount).', but found '.count($extraRevisions).'.');
            }

            foreach ($extraRevisions as $extraRevision) {
                $this->line('  - '.$this->revisionLogLine($extraRevision->id, $extraRevision->revisionNum));

                if ($this->option('dry-run')) {
                    continue;
                }

                $elementsService->deleteElement($extraRevision, true);
            }

            $prunedRevisionCount += count($extraRevisions);
        }

        $this->components->success($this->dryRunPrefix()."Finished pruning revisions. $prunedRevisionCount ".Str::plural('revision', $prunedRevisionCount).' matched.');

        return self::SUCCESS;
    }

    private function resolveSectionIds(): ?array
    {
        $handles = str((string) $this->option('section'))
            ->explode(',')
            ->map(fn (string $handle) => trim($handle))
            ->filter()
            ->values();

        if ($handles->isEmpty()) {
            if (! $this->input->isInteractive()) {
                return [];
            }

            $options = Sections::getAllSections()
                ->mapWithKeys(fn ($section) => [$section->handle => sprintf('%s (%s)', $section->name, $section->handle)])
                ->all();

            if ($options === []) {
                return [];
            }

            $handles = collect(multiselect(
                label: 'Which sections should be pruned?',
                options: $options,
                hint: 'Leave empty to prune revisions for all sections and element types.',
            ));

            if ($handles->isEmpty()) {
                return [];
            }
        }

        if ($handles->isEmpty()) {
            return [];
        }

        $sectionIds = [];

        foreach ($handles as $handle) {
            $section = Sections::getSectionByHandle($handle);

            if (! $section) {
                $this->components->error("$handle is not a valid section handle.");

                return null;
            }

            $sectionIds[] = $section->id;
        }

        return $sectionIds;
    }

    private function maxRevisions(GeneralConfig $generalConfig): int
    {
        $maxRevisions = $this->option('max-revisions');

        if ($maxRevisions !== null) {
            return (int) $maxRevisions;
        }

        if (! $this->input->isInteractive()) {
            return (int) $generalConfig->maxRevisions;
        }

        return (int) text(
            label: 'What is the max number of revisions an element can have?',
            default: (string) $generalConfig->maxRevisions,
            required: true,
            transform: fn (string $value) => trim($value),
            validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value >= 0
                ? null
                : 'The max revisions value must be an integer greater than or equal to 0.',
        );
    }

    private function elementsWithExtraRevisions(Connection $connection, array $sectionIds, int $maxRevisions): Collection
    {
        $subQuery = $connection->table(Table::REVISIONS, 'revisions')
            ->select(['canonicalId', $connection->raw('COUNT(*) as count')])
            ->groupBy('canonicalId')
            ->havingRaw('COUNT(*) > ?', [$maxRevisions])
            ->when(
                $sectionIds !== [],
                fn (Builder $query) => $query
                    ->join(Table::ENTRIES, 'entries.id', '=', 'revisions.canonicalId')
                    ->whereIn('entries.sectionId', $sectionIds),
            );

        return $connection->table($subQuery, 'duplicate_revisions')
            ->select([
                'duplicate_revisions.canonicalId as id',
                'duplicate_revisions.count',
                'type' => $connection->table(Table::ELEMENTS)
                    ->select('type')
                    ->whereColumn('id', 'duplicate_revisions.canonicalId'),
            ])
            ->get();
    }

    private function taskLabel(string $displayName, int $elementId, int $revisionCount): string
    {
        return sprintf(
            '%s%s %s (%s %s)',
            $this->dryRunPrefix(),
            $displayName,
            $elementId,
            $revisionCount,
            Str::plural('revision', $revisionCount),
        );
    }

    private function revisionLogLine(int $elementId, int $revisionNum): string
    {
        $action = $this->option('dry-run') ? 'Would delete' : 'Deleting';

        return "$action revision $revisionNum (element $elementId)";
    }

    private function dryRunPrefix(): string
    {
        return $this->option('dry-run') ? '[DRY RUN] ' : '';
    }
}
