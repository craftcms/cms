<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands\Resave;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\EntryTypes;
use Override;

class ResaveEntriesCommand extends ResaveCommand
{
    #[Override]
    protected $signature = 'craft:resave:entries'
        .self::DEFAULT_OPTIONS
        .'
        {--section= : Section handle(s), comma-separated.}
        {--all-sections : Include entries from all sections.}
        {--field= : Field handle(s) for nested entries, comma-separated.}
        {--owner-id= : Owner element ID(s), comma-separated.}
        {--type= : Entry type handle(s), comma-separated.}
        {--drafts= : Include drafts (true/false/null).}
        {--provisional-drafts= : Include provisional drafts (true/false/null).}
        {--revisions= : Include revisions (true/false/null).}
        {--propagate-to= : Site handle(s) to propagate entries to, comma-separated.}
        {--set-enabled-for-site= : Set the site-enabled status (true/false).}
    ';

    #[Override]
    protected $description = 'Re-saves entries.';

    #[Override]
    protected $aliases = ['resave/entries'];

    public function handle(EntryTypes $entryTypes): int
    {
        if (! $this->validateResaveOptions()) {
            return self::FAILURE;
        }

        $criteria = [];

        if ($this->option('all-sections')) {
            $criteria['section'] = '*';
        } elseif ($this->option('section')) {
            $criteria['section'] = str($this->option('section'))
                ->explode(',')
                ->all();
        }

        if ($this->option('field')) {
            $criteria['field'] = str($this->option('field'))
                ->explode(',')
                ->all();
        }

        if ($this->option('owner-id')) {
            $criteria['ownerId'] = str($this->option('owner-id'))
                ->explode(',')
                ->map(intval(...))
                ->all();
        }

        if ($this->option('type')) {
            $criteria['type'] = str($this->option('type'))
                ->explode(',')
                ->all();
        }

        $withFields = $this->resolvedWithFields();

        if (! empty($withFields)) {
            $handles = $entryTypes->getAllEntryTypes()
                ->filter(fn (EntryType $entryType) => $this->hasTheFields($entryType->getFieldLayout()))
                ->pluck('handle')
                ->all();

            if (isset($criteria['type'])) {
                $criteria['type'] = array_intersect($criteria['type'], $handles);
            } else {
                $criteria['type'] = $handles;
            }

            if (empty($criteria['type'])) {
                $this->components->warn('No entry types satisfy `--with-fields`.');

                return self::FAILURE;
            }
        }

        return $this->resaveElements(Entry::class, $criteria);
    }
}
