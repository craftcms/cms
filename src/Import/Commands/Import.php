<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Import as ImportFacade;
use CraftCms\Cms\Support\Facades\ImportPlan;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Json;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * If you create a new element import command that extends this class,
 * you should register it from your Plugin's boot() method via
 * $this->commands() method and pass an array containing FQCN of the new command(s).
 */
abstract class Import extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, '`@root`-relative path to the file containing the data you want to import.')
            ->addOption('site', null, InputOption::VALUE_OPTIONAL, 'The handle of the site you want to import into.')
            ->addOption('transformer', null, InputOption::VALUE_OPTIONAL, 'The fully qualified class name of the transformer you want to use to manipulate the data on import.')
            ->addOption('matchCriteria', null, InputOption::VALUE_OPTIONAL, 'An array of key-value pairs that will be used to match existing elements when importing.');
    }

    /**
     * The importer class that will be used to import the data.
     */
    abstract public static function importerClass(): string;

    /**
     * Builds an interactive prompt form for missing CLI options, normalizes match criteria, constructs an ElementImporter config from options/prompt answers, and dispatches the import.
     */
    public function handle(): int
    {
        $options = form()
            ->addIf(! $this->option('site') && Sites::isMultiSite(), fn ($form) => select(
                label: 'Which site you want to import into?',
                options: Sites::getAllSites()
                    ->mapWithKeys(fn (Site $site) => [$site->handle => $site->name])
                    ->all(),
                default: Sites::getPrimarySite()->handle,
            ), 'site')
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
            ), 'matchCriteria');

        foreach ($this->getAdditionalOptions() as $handle => $params) {
            $options->addIf(! $this->option($handle) && ($params['condition'] ?? true), $params['prompt'], $handle);
        }
        $responses = $options->submit();

        $matchCriteria = null;
        if ($this->option('matchCriteria')) {
            $matchCriteria = self::normalizeMatchCriteria($this->option('matchCriteria'));
        } elseif ($responses['matchCriteria']) {
            if (! str_starts_with((string) $responses['matchCriteria'], '=')) {
                $responses['matchCriteria'] = '='.$responses['matchCriteria'];
            }
            $matchCriteria = self::normalizeMatchCriteria($responses['matchCriteria']);
        }

        $settings = [
            ...$responses,
            ...$this->options(),
        ];
        unset($settings['matchCriteria']);

        // IMPORTANT: don't change "?:" to "??" as it'll treat an empty string passed into --optionName as valid
        $config = [
            'type' => static::importerClass(),
            'file' => $this->argument('file'),
            'transformer' => $this->option('transformer') ?: $responses['transformer'] ?: null,
            'settings' => $settings,
        ];

        $importer = ImportPlan::createImporter($config);

        if ($importer === null) {
            return self::FAILURE;
        }

        if ($matchCriteria) {
            $importer->matchCriteria($matchCriteria);
        }

        $this->components->info('Importing data into:');

        $list = [
            "Import Type: `{$importer::targetClass()}`",
            "File: `$importer->file`",
            'Transformer: '.($importer->transformer ? "`{$importer->transformerAsString()}`" : 'NULL'),
            'Match Criteria: '.($importer->matchCriteria ? json_encode($importer->matchCriteria) : 'NULL'),
        ];
        $this->components->bulletList($list);

        try {
            // $importer->validateSettings();
            $filePath = $importer::resolvedFilePath($importer->file);
            $matchCriteria = ImportHelper::normalizeMatchCriteriaFromImporterConfig($importer);
            $allData = ImportFacade::getFormattedData($filePath);
            $count = count($allData);

            foreach ($allData as $i => $item) {
                $this->components->info('Importing item ('.($i + 1)."/{$count}) ...");
                ImportFacade::importItem($importer, $item, $matchCriteria);
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
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
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

    /**
     * Returns an array of additional options that concrete classes should prompt for if missing.
     */
    protected function getAdditionalOptions(): array
    {
        return [];
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
}
