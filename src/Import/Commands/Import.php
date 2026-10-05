<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands;

use Closure;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Import\Events\ImportFinished;
use CraftCms\Cms\Import\Events\ImportStarted;
use CraftCms\Cms\Import\Events\ImportStepFinished;
use CraftCms\Cms\Import\Events\ImportStepStarted;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Import as ImportFacade;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\ImportPlan;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Json;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\form;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * If you create a new import command that extends this class,
 * you should register it from your Plugin's boot() method via
 * $this->commands() method and pass an array containing FQCN of the new command(s).
 *
 * @since 6.0.0
 */
abstract class Import extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    protected function configure(): void
    {
        $this->addArgument('source', InputArgument::REQUIRED, 'The aliased or @root-relative path to a file or a URL containing the data you want to import.');

        if (static::importerClass()::isElementImporter()) {
            $this->addOption('site', null, InputOption::VALUE_OPTIONAL, 'The handle of the site you want to import into.');
        }

        $this->addOption('transformer', null, InputOption::VALUE_OPTIONAL, 'The fully qualified class name of the transformer you want to use to manipulate the data on import.')
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
            ->addIf($this->hasOption('site') && ! $this->input->getOption('site') && Sites::isMultiSite(), fn ($form) => select(
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
                label: 'A JSON-encoded array of match criteria you’d like to use to match against existing elements. If none provided, all items will be imported as new.',
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

        $settings = array_replace($this->options(), array_filter($responses, fn ($value) => $value !== null && $value !== ''));
        unset($settings['matchCriteria']);

        // IMPORTANT: don't change "?:" to "??" as it'll treat an empty string passed into --optionName as valid
        $config = [
            'type' => static::importerClass(),
            'source' => $this->argument('source'),
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
            "Source: `$importer->source`",
            'Transformer: '.($importer->transformer ? "`{$importer->transformerAsString()}`" : 'NULL'),
            'Match Criteria: '.($importer->matchCriteria ? Json::encode($importer->matchCriteria) : 'NULL'),
        ];
        $this->components->bulletList($list);

        try {
            $importer->validate();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $attribute => $messages) {
                $this->components->error("$attribute: ".implode(' ', $messages));
            }
            $this->fail('Import configuration is invalid.');
        }

        $matchCriteria = ImportHelper::normalizeMatchCriteriaFromImporterConfig($importer);

        try {
            $allData = $importer->withLocalFile(fn (string $filePath) => ImportFacade::getFormattedData($filePath));
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $count = count($allData);

        $runId = (string) Str::uuid();
        $hasFailures = false;

        event(new ImportStarted(null, [$importer], $runId));
        event(new ImportStepStarted(null, $importer, $runId));

        foreach ($allData as $i => $item) {
            $this->components->info('Importing item ('.($i + 1)."/{$count}) ...");

            // import data
            try {
                ImportFacade::importItem($importer, $item, $matchCriteria, $runId);
            } catch (\Exception $e) {
                $hasFailures = true;

                // log and proceed further
                if ($this->input->isInteractive()) {
                    $this->components->warn('failed: '.$e->getMessage());
                } else {
                    ImportLog::warning('Couldn’t import data item '.($i + 1)."/{$count} because of the following error: ".$e->getMessage(), ['step' => $importer, 'data' => $item]);
                }
            }
        }

        event(new ImportStepFinished(null, $importer, $runId, $hasFailures));
        event(new ImportFinished(null, [$importer], $runId, $hasFailures));

        $this->components->info('Done');

        return self::SUCCESS;
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, Closure>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'source' => fn () => text(
                label: 'The aliased or @root-relative path to a file or a URL containing the data you want to import.',
                required: true,
                validate: [
                    'string',
                ]
            ),
        ];
    }

    /**
     * Returns an array of additional options that concrete classes should prompt for if missing.
     *
     * @return array<string, array{prompt: Closure, condition?: bool}>
     */
    protected function getAdditionalOptions(): array
    {
        return [];
    }

    /**
     * Strips a leading `=` from a match-criteria string and JSON-decodes it, returning null if not prefixed.
     *
     * @return array<string, mixed>|null
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
