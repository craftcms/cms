<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Utils;

use craft\base\ElementInterface;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Override;

class PruneProvisionalDraftsCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:utils:prune-provisional-drafts
        {--dry-run : Report extra provisional drafts without deleting them.}
    ';

    #[Override]
    protected $description = 'Prunes provisional drafts for elements that have more than one per user.';

    #[Override]
    protected $aliases = ['utils/prune-provisional-drafts', 'utils/prune-provisional-drafts:index', 'utils/prune-provisional-drafts/index'];

    public function handle(Connection $connection, Elements $elementsService): int
    {
        $this->components->task(
            'Finding elements with multiple provisional drafts per user',
            function () use (&$elements, $connection) {
                $elements = $this->elementsWithExtraProvisionalDrafts($connection);
            },
        );

        if ($elements->isEmpty()) {
            $this->components->success('Nothing to prune.');

            return self::SUCCESS;
        }

        $prunedDraftCount = 0;

        foreach ($elements as $element) {
            if (! class_exists($element->type)) {
                continue;
            }

            /** @var class-string<ElementInterface> $elementType */
            $elementType = $element->type;
            $extraDraftCount = $element->count - 1;
            $extraDrafts = $elementType::find()
                ->provisionalDrafts()
                ->draftOf($element->id)
                ->draftCreator($element->creatorId)
                ->site('*')
                ->unique()
                ->status(null)
                ->orderByDesc('dateUpdated')
                ->offset(1)
                ->all();

            $this->components->twoColumnDetail(
                $this->taskLabel($elementType::displayName(), $element->id, $element->creatorId, $extraDraftCount),
                '<fg=yellow;options=bold>MATCHED</>',
            );

            if (count($extraDrafts) !== $extraDraftCount) {
                $this->components->warn("Expected to find $extraDraftCount provisional ".Str::plural('draft', $extraDraftCount).', but found '.count($extraDrafts).'.');
            }

            foreach ($extraDrafts as $extraDraft) {
                $this->line('  - '.$this->draftLogLine($extraDraft->id, $extraDraft->draftId));

                if ($this->option('dry-run')) {
                    continue;
                }

                $elementsService->deleteElement($extraDraft, true);
            }

            $prunedDraftCount += count($extraDrafts);
        }

        $this->components->success($this->dryRunPrefix()."Finished pruning extra provisional drafts. $prunedDraftCount provisional ".Str::plural('draft', $prunedDraftCount).' matched.');

        return self::SUCCESS;
    }

    private function elementsWithExtraProvisionalDrafts(Connection $connection): Collection
    {
        return $connection->table(
            $connection->table(Table::DRAFTS)
                ->select(['canonicalId', 'creatorId', $connection->raw('COUNT(*) as count')])
                ->where('provisional', true)
                ->groupBy(['canonicalId', 'creatorId'])
                ->havingRaw('COUNT(*) > 1'),
            as: 'duplicate_drafts',
        )
            ->select([
                'duplicate_drafts.canonicalId as id',
                'duplicate_drafts.creatorId',
                'duplicate_drafts.count',
                'type' => $connection->table(Table::ELEMENTS)
                    ->select('type')
                    ->whereColumn('id', 'duplicate_drafts.canonicalId'),
            ])
            ->get();
    }

    private function taskLabel(string $displayName, int $elementId, int $creatorId, int $draftCount): string
    {
        return sprintf(
            '%s%s %s for user %s (%s provisional %s)',
            $this->dryRunPrefix(),
            $displayName,
            $elementId,
            $creatorId,
            $draftCount,
            Str::plural('draft', $draftCount),
        );
    }

    private function dryRunPrefix(): string
    {
        return $this->option('dry-run') ? '[DRY RUN] ' : '';
    }

    private function draftLogLine(int $elementId, int $draftId): string
    {
        $action = $this->option('dry-run') ? 'Would delete' : 'Deleting';

        return "$action provisional draft $draftId (element $elementId)";
    }
}
