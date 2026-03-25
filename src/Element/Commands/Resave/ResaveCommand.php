<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Commands\Resave;

use Craft;
use craft\base\ElementInterface;
use craft\events\MultiElementActionEvent;
use craft\helpers\ElementHelper;
use craft\services\Elements;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Jobs\ResaveElements as ResaveElementsJob;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Console\Command;
use Illuminate\Console\View\TaskResult;
use Throwable;

use function CraftCms\Cms\normalizeValue;
use function CraftCms\Cms\renderObjectTemplate;

/**
 * Base class for resave commands.
 *
 * Provides shared options and resave logic. Extend this class
 * for element-specific resave commands or custom plugin resave commands.
 */
abstract class ResaveCommand extends Command
{
    use CraftCommand;

    protected const string DEFAULT_OPTIONS = '
        {--queue : Whether to queue the resave jobs.}
        {--batch-size=100 : Elements per batch when queued.}
        {--element-id= : Element ID(s), comma-separated.}
        {--uid= : Element UID(s), comma-separated.}
        {--site= : Site handle to fetch elements from.}
        {--status=any : Element status(es), comma-separated.}
        {--offset= : Number of elements to skip.}
        {--limit= : Max number of elements to resave.}
        {--update-search-index : Update search indexes for resaved elements.}
        {--touch : Update the `dateUpdated` timestamp.}
        {--set= : Attribute name to set on each element.}
        {--to= : Value for the --set attribute.}
        {--if-empty : Only set the attribute if it is currently empty.}
        {--if-invalid : Only set the attribute if the current value is invalid.}
        {--with-fields=* : Only resave elements with these field handles.}
    ';

    /**
     * Resolved site IDs for `--propagate-to`, or null if not propagating.
     *
     * @var int[]|null
     */
    private ?array $resolvedPropagateTo = null;

    /**
     * Normalized draft/revision option values.
     */
    private ?bool $resolvedDrafts = false;

    private ?bool $resolvedProvisionalDrafts = false;

    private ?bool $resolvedRevisions = false;

    /**
     * @var array<int, string>|null
     */
    private ?array $resolvedWithFields = null;

    /**
     * Returns [[to]] normalized to a callable.
     *
     * @return callable(ElementInterface): mixed
     */
    public static function normalizeTo(?string $to): callable
    {
        if ($to === ':empty:') {
            return fn () => '';
        }

        if (str_starts_with((string) $to, '=')) {
            $template = substr((string) $to, 1);

            return fn (ElementInterface $element) => renderObjectTemplate($template, $element);
        }

        if (preg_match('/^fn\s*\(\s*(?:\$(\w+)\s*)?\)\s*=>\s*(.+)/', (string) $to, $match)) {
            $var = $match[1];
            $php = sprintf('return %s;', Str::chopStart(rtrim($match[2], ';'), 'return '));

            return function (ElementInterface $element) use ($var, $php) {
                if ($var) {
                    ${$var} = $element;
                }

                return eval($php);
            };
        }

        return static fn (ElementInterface $element) => $element->$to;
    }

    /**
     * Validates and normalizes shared resave options.
     *
     * Call at the start of each command's `handle()` method.
     */
    protected function validateResaveOptions(): bool
    {
        // Normalize drafts/provisionalDrafts/revisions
        foreach (['drafts', 'provisional-drafts', 'revisions'] as $optionName) {
            $value = $this->optionalOption($optionName);

            if ($value === null) {
                $resolved = false;
            } elseif (is_string($value)) {
                $normalized = normalizeValue($value);
                $resolved = $normalized !== null ? (bool) $normalized : null;
            } else {
                $resolved = $value;
            }

            match ($optionName) {
                'drafts' => $this->resolvedDrafts = $resolved,
                'provisional-drafts' => $this->resolvedProvisionalDrafts = $resolved,
                'revisions' => $this->resolvedRevisions = $resolved,
            };
        }

        // Validate --propagate-to (entries-only option)
        $propagateTo = $this->optionalOption('propagate-to');

        if ($propagateTo) {
            $siteHandles = str($propagateTo)
                ->explode(',')
                ->filter()
                ->all();
            $this->resolvedPropagateTo = [];

            foreach ($siteHandles as $siteHandle) {
                $site = Sites::getSiteByHandle($siteHandle, true);

                if (! $site) {
                    $this->components->error("Invalid site handle: $siteHandle");

                    return false;
                }

                $this->resolvedPropagateTo[] = $site->id;
            }

            if ($this->option('set')) {
                $this->components->error("--propagate-to can\u{2019}t be coupled with --set.");

                return false;
            }
        }

        // Validate --set requires --to
        if ($this->option('set') && ! $this->option('to')) {
            $this->components->error('--to is required when using --set.');

            return false;
        }

        $this->resolvedWithFields = $this->normalizeOptionValues('with-fields');

        return true;
    }

    /**
     * Builds base element query criteria from shared options.
     *
     * @return array<string, mixed>
     */
    protected function baseCriteria(): array
    {
        $criteria = [
            'drafts' => $this->resolvedDrafts,
            'provisionalDrafts' => $this->resolvedProvisionalDrafts,
            'revisions' => $this->resolvedRevisions,
        ];

        if ($this->resolvedProvisionalDrafts !== false && $this->resolvedDrafts == false) {
            $criteria['drafts'] = true;
        }

        $elementId = $this->option('element-id');

        if ($elementId) {
            $criteria['id'] = is_int($elementId)
                ? $elementId
                : str($elementId)->explode(',')->all();
        }

        $uid = $this->option('uid');

        if ($uid) {
            $criteria['uid'] = str($uid)
                ->explode(',')
                ->all();
        }

        $site = $this->option('site');

        if ($site) {
            $criteria['site'] = $site;
        }

        $status = $this->option('status');

        if ($status === 'any') {
            $criteria['status'] = null;
        } elseif ($status) {
            $criteria['status'] = str($status)
                ->explode(',')
                ->all();
        }

        $offset = $this->option('offset');

        if ($offset !== null) {
            $criteria['offset'] = (int) $offset;
        }

        $limit = $this->option('limit');

        if ($limit !== null) {
            $criteria['limit'] = (int) $limit;
        }

        return $criteria;
    }

    /**
     * Resaves elements, either via the queue or inline.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $criteria
     */
    protected function resaveElements(string $elementType, array $criteria = []): int
    {
        $criteria += $this->baseCriteria();

        if ($this->option('queue')) {
            dispatch(new ResaveElementsJob(
                elementType: $elementType,
                criteria: $criteria,
                updateSearchIndex: (bool) $this->option('update-search-index'),
                set: $this->option('set'),
                to: $this->option('to'),
                ifEmpty: (bool) $this->option('if-empty'),
                ifInvalid: (bool) $this->option('if-invalid'),
                touch: (bool) $this->option('touch'),
                batchSize: (int) $this->option('batch-size'),
            ));

            $this->components->success($elementType::pluralDisplayName().' queued to be resaved.');

            return self::SUCCESS;
        }

        $query = $elementType::find();

        Typecast::configure($query, $criteria);

        return $this->runResaveLoop($query);
    }

    /**
     * Returns whether a field layout has any of the fields specified by `--with-fields`.
     */
    protected function hasTheFields(FieldLayout $fieldLayout): bool
    {
        $withFields = $this->resolvedWithFields();

        if (empty($withFields)) {
            return true;
        }

        $fieldsService = app(Fields::class);

        foreach ($withFields as $handle) {
            $field = $fieldsService->getFieldByHandle($handle);

            if ($field && $fieldLayout->getFieldByUid($field->uid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runs the inline resave loop with per-element progress output.
     */
    private function runResaveLoop(ElementQueryInterface $query): int
    {
        $elementType = $query->elementType;
        $count = $query->count();

        if ($count === 0) {
            $this->components->warn('No '.$elementType::pluralLowerDisplayName().' exist for that criteria.');

            return self::SUCCESS;
        }

        if ($query->offset) {
            $count = max($count - (int) $query->offset, 0);
        }

        if ($query->limit) {
            $count = min($count, (int) $query->limit);
        }

        $to = $this->option('set') ? self::normalizeTo($this->option('to')) : null;

        $label = isset($this->resolvedPropagateTo) ? 'Propagating' : 'Resaving';
        $elementsText = $count === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName();

        $this->components->twoColumnDetail("$label $count $elementsText");

        $elementsService = Craft::$app->getElements();
        $fail = false;
        $elements = $query
            ->cursor()
            ->skip((int) ($query->offset ?? 0));

        if ($query->limit) {
            $elements = $elements->take((int) $query->limit);
        }

        $elements = $elements->values();

        $set = $this->option('set');
        $ifEmpty = (bool) $this->option('if-empty');
        $ifInvalid = (bool) $this->option('if-invalid');
        $setEnabledForSite = $this->optionalOption('set-enabled-for-site');

        if ($setEnabledForSite !== null) {
            $setEnabledForSite = (bool) normalizeValue($setEnabledForSite);
        }

        $beforeCallback = function (MultiElementActionEvent $e) use ($query, $to, $set, $ifEmpty, $ifInvalid, $setEnabledForSite) {
            if ($e->query !== $query) {
                return;
            }

            $element = $e->element;

            if (isset($this->resolvedPropagateTo)) {
                $siteStatuses = ElementHelper::siteStatusesForElement($element);

                foreach ($this->resolvedPropagateTo as $siteId) {
                    $siteStatuses[$siteId] = $setEnabledForSite ?? $siteStatuses[$siteId] ?? $element->getEnabledForSite();
                }

                $element->setEnabledForSite($siteStatuses);
            } else {
                if ($setEnabledForSite !== null) {
                    $element->setEnabledForSite($setEnabledForSite);
                }

                try {
                    if (isset($set)) {
                        $shouldSet = true;

                        if ($ifEmpty) {
                            if (! ElementHelper::isAttributeEmpty($element, $set)) {
                                $shouldSet = false;
                            }
                        } elseif ($ifInvalid) {
                            $element->setScenario(Element::SCENARIO_LIVE);

                            if ($element->validate($set) && $element->validate("field:$set")) {
                                $shouldSet = false;
                            }
                        }

                        if ($shouldSet) {
                            $element->{$set} = $to($element);
                        }
                    }
                } catch (Throwable $e) {
                    throw new InvalidElementException($element, $e->getMessage());
                }
            }
        };

        foreach ($elements as $position => $element) {
            $position++;

            $this->components->task(
                "⇂ [$position/$count] $element ($element->id)",
                function () use ($elementsService, $position, $element, $query, $beforeCallback, &$fail) {
                    $event = new MultiElementActionEvent([
                        'query' => $query,
                        'element' => $element,
                        'position' => $position,
                    ]);

                    try {
                        $beforeCallback($event);

                        if (isset($this->resolvedPropagateTo)) {
                            $this->propagateElement($element, $elementsService);
                        } else {
                            if ($this->resolvedRevisions === false) {
                                $label = $element->getUiLabel();
                                $label = $label !== '' ? "$label ($element->id)" : sprintf('%s %s', $element::lowerDisplayName(), $element->id);

                                try {
                                    if (ElementHelper::isRevision($element)) {
                                        throw new InvalidElementException($element, "Skipped resaving $label because it's a revision.");
                                    }
                                } catch (Throwable $rootException) {
                                    throw new InvalidElementException($element, "Skipped resaving $label due to an error obtaining its root element: ".$rootException->getMessage());
                                }
                            }

                            $element->setScenario(Element::SCENARIO_ESSENTIALS);
                            $element->resaving = true;
                            $elementsService->saveElement(
                                $element,
                                updateSearchIndex: (bool) $this->option('update-search-index'),
                                forceTouch: (bool) $this->option('touch'),
                                saveContent: true,
                            );
                        }

                        $event->exception = null;
                    } catch (Throwable $exception) {
                        report($exception);
                        $event->exception = $exception;
                    }

                    if ($event->exception) {
                        $this->components->error('error: '.$event->exception->getMessage());
                        $fail = true;

                        return TaskResult::Failure;
                    }

                    if ($element->errors()->isNotEmpty()) {
                        $this->components->error('failed: '.implode(', ', $element->getErrorSummary(true)));
                        $fail = true;

                        return TaskResult::Failure;
                    }

                    return TaskResult::Success;
                }
            );
        }

        $label = isset($this->resolvedPropagateTo) ? 'propagating' : 'resaving';
        $this->components->twoColumnDetail('..', $fail ? "<fg=red;options=bold>Done $label $elementsText.</>" : "<fg=green;options=bold>Done $label $elementsText.</>");

        return $fail ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Returns the value of an option that may not be defined on all commands.
     *
     * @return mixed The option value, or null if the option is not defined.
     */
    protected function optionalOption(string $name): mixed
    {
        return $this->hasOption($name) ? $this->input->getOption($name) : null;
    }

    protected function propagateElement(ElementInterface $element, Elements $elementsService): void
    {
        $supportedSites = collect(ElementHelper::supportedSitesForElement($element))
            ->keyBy('siteId')
            ->all();
        $elementSiteIds = array_intersect($this->resolvedPropagateTo ?? [], array_keys($supportedSites));
        $elementType = $element::class;

        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        $element->newSiteIds = [];

        foreach ($elementSiteIds as $siteId) {
            if ($siteId === $element->siteId) {
                continue;
            }

            $siteElement = $elementsService->getElementById($element->id, $elementType, $siteId);

            if ($siteElement && $siteElement->dateUpdated >= $element->dateUpdated) {
                continue;
            }

            $clone = clone $element;
            $clone->siteId = $siteId;
            $clone->propagating = true;
            $clone->isNewForSite = $siteElement === null;
            $clone->enabled = $element->getEnabledForSite($siteId);

            $elementsService->saveElement(
                $clone,
                propagate: false,
                updateSearchIndex: false,
                saveContent: true,
            );
        }

        $element->markAsDirty();
        $element->afterPropagate(false);
    }

    /**
     * @return array<int, string>
     */
    protected function resolvedWithFields(): array
    {
        return $this->resolvedWithFields ??= $this->normalizeOptionValues('with-fields');
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeOptionValues(string $name): array
    {
        $value = $this->optionalOption($name);

        if ($value === null) {
            return [];
        }

        return collect(is_array($value) ? $value : [$value])
            ->flatMap(function (mixed $item) {
                if (! is_string($item)) {
                    return [$item];
                }

                return Str::of($item)
                    ->explode(',')
                    ->all();
            })
            ->filter(fn (mixed $item) => $item !== null && $item !== '')
            ->map(fn (mixed $item) => (string) $item)
            ->values()
            ->all();
    }
}
