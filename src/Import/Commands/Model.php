<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Import\Importers\ModelImporter;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Json;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Validation\ValidationException;
use Override;

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Model extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:import:model
        {className : The fully qualified class name of the Eloquent Model you want to import into.}
        {file : `@root`-relative path to the file containing data you want to import.}
        {--transformer= : The fully qualified class name of the transformer you want to use to manipulate the data on import.}
        {--matchCriteria= : An array of key-value pairs that will be used to match existing elements when importing.}
    ';

    #[Override]
    protected $description = 'Imports data into specified Eloquent Model';

    #[Override]
    protected $aliases = ['import/model'];

    /**
     * Builds an interactive prompt form for missing CLI options, normalizes match criteria, constructs an ElementImporter config from options/prompt answers, and dispatches the import.
     */
    public function handle(): int
    {
        $responses = form()
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
        $importConfig = (new ModelImporter)
            ->className($this->argument('className'))
            ->file($this->argument('file'))
            ->transformer($this->option('transformer') ?: $responses['transformer'] ?: null);

        if ($matchCriteria) {
            $importConfig->matchCriteria($matchCriteria);
        }

        $this->components->info('Importing data into:');

        $list = [
            "Class Name: `{$importConfig->className}`",
            "File: `$importConfig->file`",
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
            'className' => fn () => text(
                label: 'Provide class name of the Eloquent Model you want to import into, e.g. App\Models\MyModel',
                required: true,
                validate: [
                    'string',
                ],
            ),
            // todo (iwona): do we want to support URLs containing all the data (like in feed me where you can use rss feed) or just files?
            'file' => fn () => text(
                label: 'The `@root`-relative path to the file containing the data you want to import',
                required: true,
                validate: [
                    'string',
                ],
            ),
        ];
    }
}
