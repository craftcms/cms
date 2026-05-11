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

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Element extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:import:element
        {--elementType= : The element type you want to import.}
        {--file= : `@root`-relative path to the file containing data you want to import.}
        {--site= : The handle of the site you want to import into.}
        {--transformer= : The transformer you want to use to manipulate the data on import.}
    ';

    #[\Override]
    protected $description = 'Imports data into specified Craft CMS element type';

    #[\Override]
    protected $aliases = ['import/element'];

    public function handle(): int
    {
        $responses = form()
            ->addIf(! $this->option('elementType'), fn ($form) => select(
                label: 'Which element type you want to import into?',
                options: collect(Elements::getAllElementTypes())
                    ->all()
            ), 'elementType')
            // TODO: do we want to support URLs containing all the data (like in feed me where you can use rss feed) or just files?
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
            // TODO: maybe change this to a select field and show all available transformers? but then we'd still have to allow for custom ones too
            ->addIf(! $this->option('transformer'), fn () => text(
                label: 'The transformer you want to use to manipulate the data on import',
                validate: [
                    'string',
                ]
            ), 'transformer')
            ->submit();

        // important: don't change "?:" to "??" as it'll treat an empty string passed into --optionName as valid
        $importConfig = (new ElementImporter)
            ->className($this->option('elementType') ?: $responses['elementType'])
            ->file($this->option('file') ?: $responses['file'])
            ->site($this->option('site') ?: $responses['site'] ?? Sites::getPrimarySite()->handle)
            ->transformer($this->option('transformer') ?: $responses['transformer'] ?: null);

        $this->components->info("element type: `{$importConfig->className}`");
        $this->components->info("file: `{$importConfig->file}`");
        $this->components->info("site: `{$importConfig->site->name}`");

        Import::import($importConfig);

        return self::SUCCESS;
    }
}
