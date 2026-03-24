<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Utils;

use Craft;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Override;

class PruneOrphanedEntriesCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:utils:prune-orphaned-entries';

    #[Override]
    protected $description = 'Prunes orphaned entries for each site.';

    #[Override]
    protected $aliases = ['utils/prune-orphaned-entries', 'utils/prune-orphaned-entries:index', 'utils/prune-orphaned-entries/index'];

    public function handle(Connection $connection, Sites $sites): int
    {
        if (! $sites->isMultiSite()) {
            $this->components->warn('This command should only be run for multi-site installs.');
        }

        $elements = Craft::$app->getElements();
        $totalDeleted = 0;

        foreach ($sites->getAllSites() as $site) {
            $entries = Entry::find()
                ->status(null)
                ->siteId($site->id)
                ->whereNotNull('entries.primaryOwnerId')
                ->whereNotExists(
                    $connection->table(Table::ELEMENTS_SITES, 'es')
                        ->whereColumn('es.elementId', 'entries.primaryOwnerId')
                        ->where('es.siteId', $site->id)
                )
                ->all();

            $entryCount = count($entries);

            $this->components->twoColumnDetail(
                sprintf('Site "%s"', $site->getName()),
                $entryCount === 0
                    ? '<fg=green;options=bold>CLEAN</>'
                    : sprintf('<fg=yellow;options=bold>%s %s</>', $entryCount, Str::plural('ORPHAN', $entryCount)),
            );

            if ($entryCount === 0) {
                continue;
            }

            foreach ($entries as $entry) {
                $this->components->task(
                    sprintf('Deleting entry %s in %s', $entry->id, $site->getName()),
                    function () use ($elements, $entry) {
                        $elements->deleteElementForSite($entry);
                    }
                );

                $totalDeleted++;
            }
        }

        if ($totalDeleted === 0) {
            $this->components->success('No orphaned entries found.');

            return self::SUCCESS;
        }

        $this->components->success("Finished pruning orphaned entries. Deleted $totalDeleted ".Str::plural('entry', $totalDeleted).'.');

        return self::SUCCESS;
    }
}
