<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Json;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Validation\ValidationException;
use Override;

use function CraftCms\Cms\t;
use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Element extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:import:element
        {elementType : The fully qualified class name of the element type you want to import into.}
        {file : `@root`-relative path to the file containing data you want to import.}
        {--site= : The handle of the site you want to import into.}
        {--fieldLayoutProvider= : The UID of the field layout provider you want to use.}
        {--transformer= : The fully qualified class name of the transformer you want to use to manipulate the data on import.}
        {--matchCriteria= : An array of key-value pairs that will be used to match existing elements when importing.}
    ';

    #[Override]
    protected $description = 'Imports data into specified Craft CMS element type';

    #[Override]
    protected $aliases = ['import/element'];

    /**
     * Builds an interactive prompt form for missing CLI options, normalizes match criteria, constructs an ElementImporter config from options/prompt answers, and dispatches the import.
     */
    public function handle(): int
    {
        $fieldLayoutProviderOptions = ImportHelper::flattenLabelValueArray(
            ImportHelper::getAvailableFieldLayoutProviders($this->argument('elementType'))
        );
        $fieldLayoutProviderOptions = array_merge(['' => t('None - specified in the data file')], $fieldLayoutProviderOptions);
        $responses = form()
            ->addIf(! $this->option('site') && Sites::isMultiSite(), fn ($form) => select(
                label: 'Which site you want to import into?',
                options: Sites::getAllSites()
                    ->mapWithKeys(fn (Site $site) => [$site->handle => $site->name])
                    ->all(),
                default: Sites::getPrimarySite()->handle,
            ), 'site')
            ->addIf(! $this->option('fieldLayoutProvider'), fn () => select(
                label: 'Provide UID, ID, or type of the field layout provider you want to use.',
                options: $fieldLayoutProviderOptions,
            ), 'fieldLayoutProvider')
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
            ->className($this->argument('elementType'))
            ->file($this->argument('file'))
            ->site($this->option('site') ?: $responses['site'] ?? Sites::getPrimarySite()->handle)
            ->fieldLayout($this->option('fieldLayoutProvider') ?: $responses['fieldLayoutProvider'] ?: null)
            ->transformer($this->option('transformer') ?: $responses['transformer'] ?: null);

        if ($matchCriteria) {
            $importConfig->matchCriteria($matchCriteria);
        }

        $this->components->info('Importing data into:');

        $list = [
            "Element Type: `{$importConfig->className}`",
            "File: `$importConfig->file`",
            "Site: `{$importConfig->site->name}`",
            'Field Layout Provider: '.($importConfig->fieldLayout ? "`$importConfig->fieldLayout`" : 'NULL'),
            'Transformer: '.($importConfig->transformer ? "`{$importConfig->transformerAsString()}`" : 'NULL'),
            'Match Criteria: '.($importConfig->matchCriteria ? json_encode($importConfig->matchCriteria) : 'NULL'),
        ];
        $this->components->bulletList($list);

        try {
            $importConfig->validateSettings();
            $filePath = $importConfig::resolvedFilePath($importConfig->file);
            $matchCriteria = ImportHelper::normalizeMatchCriteriaFromImporterConfig($importConfig);
            $allData = Import::getFormattedData($filePath);
            $count = count($allData);

            foreach ($allData as $i => $item) {
                $this->components->info('Importing item ('.($i + 1)."/{$count}) ...");
                Import::importItem($importConfig, $item, $matchCriteria);
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $attribute => $messages) {
                $this->components->error("$attribute: ".implode(' ', $messages));
            }
            $this->fail('Import configuration is invalid.');
        }

        $this->components->info('Done');

        return self::SUCCESS;
    }

    /**
     * Strips a leading `=` from a match-criteria string and JSON-decodes it, returning null if not prefixed.
     */
    private static function normalizeMatchCriteria(string $matchCriteria): ?array
    {
        if (str_starts_with($matchCriteria, '=')) {
            $matchCriteria = substr($matchCriteria, 1);
        }

        try {
            return Json::decode($matchCriteria);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'elementType' => fn () => select(
                label: 'Provide class name of the element type you want to import into, e.g. CraftCms\Cms\Entry\Elements\Entry',
                options: ImportHelper::flattenLabelValueArray(
                    ImportHelper::getImportableElementTypes()->all()
                ),
            ),
            // todo (iwona): do we want to support URLs containing all the data (like in feed me where you can use rss feed) or just files?
            'file' => fn () => text(
                label: 'The `@root`-relative path to the file containing the data you want to import',
                required: true,
                validate: [
                    'string',
                ]
            ),
        ];
    }
}
