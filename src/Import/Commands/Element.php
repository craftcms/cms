<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Console\Command;
use Override;

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Element extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:import:element
        {--elementType= : The fully qualified class name of the element type you want to import into.}
        {--file= : `@root`-relative path to the file containing data you want to import.}
        {--transformer= : The fully qualified class name of the transformer you want to use to manipulate the data on import.}
        {--matchCriteria= : An array of key-value pairs that will be used to match existing elements when importing.}
        {--site= : The handle of the site you want to import into.}
    ';

    #[Override]
    protected $description = 'Imports data into specified Craft CMS element type';

    #[Override]
    protected $aliases = ['import/element'];

    /**
     * Builds an interactive prompt form for missing CLI options, normalizes match criteria, constructs an ElementImporter config from options/prompt answers, and dispatches the import.
     *
     * @return int
     */
    public function handle(): int
    {
        $responses = form()
            ->addIf(! $this->option('elementType'), fn ($form) => select(
                label: 'Which element type you want to import into?',
                options: collect(Elements::getAllElementTypes())
                    ->all()
            ), 'elementType')
            // todo (iwona): do we want to support URLs containing all the data (like in feed me where you can use rss feed) or just files?
            ->addIf(! $this->option('file'), fn () => text(
                label: 'The `@root`-relative path to the file containing the data you want to import',
                required: true,
                validate: [
                    'string',
                ]
            ), 'file')
            ->addIf(! $this->option('site') && Sites::isMultiSite(), fn ($form) => select(
                label: 'Which site you want to import into?',
                options: Sites::getAllSites()
                    ->mapWithKeys(fn (Site $site) => [$site->handle => $site->name])
                    ->all(),
                default: Sites::getPrimarySite()->handle,
            ), 'site')
            // todo (iwona): maybe change this to a select field and show all available transformers? but then we'd still have to allow for custom ones too
            ->addIf(! $this->option('transformer'), fn () => text(
                label: 'The transformer you want to use to manipulate the data on import (fully qualified class name for the transformer)',
                validate: [
                    'string',
                ]
            ), 'transformer')
            ->addIf(! $this->option('matchCriteria'), fn () => text(
                label: 'A JSON-encoded array of match criteria you’d like to use to match against existing elements. If none provided, ID will be used for matching.',
                validate: [
                    'string',
                ]
            ), 'matchCriteria')
            ->submit();

        $matchCriteria = null;
        if ($this->option('matchCriteria')) {
            $matchCriteria = self::normalizeMatchCriteria($this->option('matchCriteria'));
        } elseif ($responses['matchCriteria']) {
            if (! str_starts_with((string) $responses['matchCriteria'], '=')) {
                $responses['matchCriteria'] = '='.$responses['matchCriteria'];
            }
            $matchCriteria = self::normalizeMatchCriteria($responses['matchCriteria']);
        }

        // IMPORTANT: don't change "?:" to "??" as it'll treat an empty string passed into --optionName as valid
        $importConfig = (new ElementImporter)
            ->className($this->option('elementType') ?: $responses['elementType'])
            ->file($this->option('file') ?: $responses['file'])
            ->site($this->option('site') ?: $responses['site'] ?? Sites::getPrimarySite()->handle)
            ->transformer($this->option('transformer') ?: $responses['transformer'] ?: null);

        if ($matchCriteria) {
            $importConfig->matchCriteria($matchCriteria);
        }

        $this->components->info("element type: `{$importConfig->className}`");
        $this->components->info("file: `{$importConfig->file}`");
        $this->components->info("site: `{$importConfig->site->name}`");

        Import::import($importConfig);

        return self::SUCCESS;
    }

    /**
     * Strips a leading `=` from a match-criteria string and JSON-decodes it, returning null if not prefixed.
     */
    private static function normalizeMatchCriteria(string $matchCriteria): ?array
    {
        if (str_starts_with($matchCriteria, '=')) {
            $json = substr($matchCriteria, 1);

            return json_decode($json, true);
        }

        return null;
    }
}
