<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands;

use craft\services\Elements;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Site\Data\Site;
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $responses = form()
            ->addIf(! $this->option('elementType'), fn ($form) => select(
                label: 'Which element type you want to import into?',
                options: collect((new Elements)->getAllElementTypes())
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
        $config['elementImport'] = true;
        $config['className'] = $this->option('elementType') ?: $responses['elementType'];
        $config['file'] = $this->option('file') ?: $responses['file'];
        $config['site'] = $this->option('site') ?: $responses['site'] ?? Sites::getPrimarySite()->handle;
        $config['transformer'] = $this->option('transformer') ?: $responses['transformer'];

        //        $validator = Validator::make($config, ElementImporter::getRules());
        //
        //        if ($validator->fails()) {
        //            foreach ($validator->errors()->all() as $error) {
        //                $this->error($error);
        //            }
        //            return self::FAILURE;
        //        }
        //
        //        $importConfig = new ElementImporter($config);

        $importConfig = new ElementImporter($config);

        $this->components->info("element type: `{$importConfig->className}`");
        $this->components->info("file: `{$importConfig->file}`");
        $this->components->info("site: `{$importConfig->site}`");
        $this->components->info('transformer: '.$importConfig->transformer::class);

        Import::import($importConfig);

        return self::SUCCESS;
    }
}
