<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\DefaultableFieldInterface;
use craft\base\Event as YiiEvent;
use craft\base\FieldInterface;
use craft\console\Controller;
use craft\elements\Category;
use craft\elements\Tag;
use craft\events\DefineConsoleActionsEvent;
use craft\events\MultiElementActionEvent;
use craft\helpers\Console;
use craft\models\CategoryGroup;
use craft\models\TagGroup;
use craft\services\Elements;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Element\Commands\Resave\ResaveCommand;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\ElementResaveCommandsResolving;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Jobs\ResaveElements;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Throwable;
use yii\console\ExitCode;

use function CraftCms\Cms\normalizeValue;

/**
 * Allows you to bulk-save elements.
 *
 * See [Bulk-Resaving Elements](https://craftcms.com/knowledge-base/bulk-resaving-elements) for examples.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.15
 * @deprecated in 6.0.0. Use the `craft:resave:*` Artisan commands instead.
 */
class ResaveController extends Controller
{
    /**
     * Returns [[to]] normalized to a callable.
     *
     * @param string|null $to
     * @return callable
     * @since 4.2.6
     * @internal
     */
    final public static function normalizeTo(?string $to): callable
    {
        return ResaveCommand::normalizeTo($to);
    }

    /**
     * @var bool Whether the elements should be resaved via a queue job.
     * @since 3.7.0
     */
    public bool $queue = false;

    /**
     * @var int The number of entries that should be resaved per queue job, if --queue is passed.
     * @since 5.7.0
     */
    public int $batchSize = 100;

    /**
     * @var bool Whether to resave element drafts.
     * Set to `null` if all elements should be resaved regardless of whether they’re drafts.
     * @since 3.6.5
     */
    public bool|string|null $drafts = null;

    /**
     * @var bool Whether to resave provisional element drafts.
     * Set to `null` if all elements should be resaved regardless of whether they’re provisional drafts.
     * @since 3.7.0
     */
    public bool|string|null $provisionalDrafts = null;

    /**
     * @var bool|string Whether to resave element revisions.
     * Set to `null` if all elements should be resaved regardless of whether they’re revisions.
     * @since 3.7.35
     */
    public bool|string|null $revisions = null;

    /**
     * @var int|string|null The ID(s) of the elements to resave.
     */
    public string|int|null $elementId = null;

    /**
     * @var string|null The UUID(s) of the elements to resave.
     */
    public ?string $uid = null;

    /**
     * @var string|null The site handle to fetch elements from.
     */
    public ?string $site = null;

    /**
     * @var string|int[]|null Comma-separated site handles to propagate entries to.
     *
     * When this is set, the entry will *only* be saved for this site.
     *
     * @since 4.4.7
     */
    public string|array|null $propagateTo = null;

    /**
     * @var string The status(es) of elements to resave. Can be set to multiple comma-separated statuses.
     */
    public string $status = 'any';

    /**
     * @var int|null The number of elements to skip.
     */
    public ?int $offset = null;

    /**
     * @var int|null The number of elements to resave.
     */
    public ?int $limit = null;

    /**
     * @var bool Whether to update the search indexes for the resaved elements.
     */
    public bool $updateSearchIndex = false;

    /**
     * @var bool Whether to update the `dateUpdated` timestamp for the elements.
     * @since 4.2.4
     */
    public bool $touch = false;

    /**
     * @var string|null The group handle(s) to save categories/tags/users from. Can be set to multiple comma-separated groups.
     */
    public ?string $group = null;

    /**
     * @var string|null The section handle(s) to save entries from. Can be set to multiple comma-separated sections.
     */
    public ?string $section = null;

    /**
     * @var bool Whether all sections’ entries should be saved.
     * @since 5.2.0
     */
    public bool $allSections = false;

    /**
     * @var string|null The type handle(s) of the elements to resave.
     * @since 3.1.16
     */
    public ?string $type = null;

    /**
     * @var string|null The volume handle(s) to save assets from. Can be set to multiple comma-separated volumes.
     */
    public ?string $volume = null;

    /**
     * @var string|null The field handle to save nested entries for.
     */
    public ?string $field = null;

    /**
     * @var string|int[]|null Comma-separated list of owner element IDs.
     * @since 4.5.6
     */
    public string|array|null $ownerId = null;

    /**
     * @var string|null Comma-separated list of country codes.
     * @since 4.5.6
     */
    public ?string $countryCode = null;

    /**
     * @var string[]|FieldInterface[] Only resave elements that have custom fields with these global field handles.
     * @since 5.5.0
     */
    public array $withFields = [];

    /**
     * @var string|null An attribute name that should be set for each of the elements. The value will be determined by --to.
     * @since 3.7.29
     */
    public ?string $set = null;

    /**
     * @var bool|null The site-enabled status that should be set on the entry, for the site it’s initially being saved/propagated to.
     * @since 4.4.7
     */
    public ?bool $setEnabledForSite = null;

    /**
     * @var string|null The value that should be set on the --set attribute.
     *
     * The following value types are supported:
     * - An attribute name: `--to myCustomField`
     * - An object template: `--to "={myCustomField|lower}"`
     * - A raw value: `--to "=foo bar"`
     * - A PHP arrow function: `--to "fn(\\$element) => \\$element->callSomething()"`
     * - An empty value: `--to :empty:`
     *
     * @since 3.7.29
     */
    public ?string $to = null;

    /**
     * @var bool Sets the specified fields to their default values.
     * @since 5.10.0
     */
    public bool $toDefault = false;

    /**
     * @var bool Whether the `--set` attribute should only be set if it doesn’t have a value.
     * @since 3.7.29
     */
    public bool $ifEmpty = false;

    /**
     * @var bool Whether the `--set` attribute should only be set if the current value doesn’t validate.
     * @since 5.1.0
     */
    public bool $ifInvalid = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'queue';
        $options[] = 'batchSize';
        $options[] = 'elementId';
        $options[] = 'uid';
        $options[] = 'site';
        $options[] = 'status';
        $options[] = 'offset';
        $options[] = 'limit';
        $options[] = 'updateSearchIndex';
        $options[] = 'touch';

        switch ($actionID) {
            case 'all':
                $options[] = 'withFields';
                break;
            case 'addresses':
                $options[] = 'ownerId';
                $options[] = 'countryCode';
                $options[] = 'withFields';
                break;
            case 'assets':
                $options[] = 'volume';
                $options[] = 'withFields';
                break;
            case 'tags':
            case 'users':
            case 'categories':
                $options[] = 'group';
                $options[] = 'withFields';
                break;
            case 'entries':
                $options[] = 'section';
                $options[] = 'allSections';
                $options[] = 'field';
                $options[] = 'ownerId';
                $options[] = 'type';
                $options[] = 'drafts';
                $options[] = 'provisionalDrafts';
                $options[] = 'revisions';
                $options[] = 'propagateTo';
                $options[] = 'setEnabledForSite';
                $options[] = 'withFields';
                break;
        }

        $options[] = 'set';
        $options[] = 'to';
        $options[] = 'toDefault';
        $options[] = 'ifEmpty';
        $options[] = 'ifInvalid';

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Can't default these properties to false because then yii\console\Controller::runAction() will
        // typecast their values to booleans
        foreach (['drafts', 'provisionalDrafts', 'revisions'] as $property) {
            $this->$property ??= false;
            if (is_string($this->$property)) {
                $value = normalizeValue($this->$property);
                $this->$property = $value !== null ? (bool)$value : null;
            }
        }

        if (isset($this->propagateTo)) {
            $siteHandles = str($this->propagateTo)->explode(',')->filter()->all();
            $this->propagateTo = [];
            foreach ($siteHandles as $siteHandle) {
                $site = Sites::getSiteByHandle($siteHandle, true);
                if (!$site) {
                    $this->stderr("Invalid site handle: $siteHandle" . PHP_EOL, Console::FG_RED);
                    return false;
                }
                $this->propagateTo[] = $site->id;
            }

            if (isset($this->set)) {
                $this->stderr('--propagate-to can’t be coupled with --set.' . PHP_EOL, Console::FG_RED);
                return false;
            }
        }

        if (isset($this->set) && !isset($this->to) && !$this->toDefault) {
            $this->stderr('--to or --to-default is required when using --set.' . PHP_EOL, Console::FG_RED);
            return false;
        }

        if (!empty($this->withFields)) {
            $fieldsService = Craft::$app->getFields();

            foreach ($this->withFields as $i => $field) {
                if (!$field instanceof FieldInterface) {
                    $handle = $field;
                    $field = $fieldsService->getFieldByHandle($handle);
                    if (!$field) {
                        $this->stderr("Invalid field: `$handle`" . PHP_EOL, Console::FG_RED);
                        return false;
                    }
                }
                $this->withFields[$i] = $field;
            }
        }

        if ($this->toDefault) {
            if (empty($this->withFields) && !isset($this->set)) {
                $this->stderr('--with-fields or --set is required when using --to-default.' . PHP_EOL, Console::FG_RED);
                return false;
            }

            $fieldsService = Craft::$app->getFields();

            if (isset($this->set)) {
                $field = $fieldsService->getFieldByHandle($this->set);
                if (!$field) {
                    $this->stderr("Invalid field handle: $this->set", Console::FG_RED);
                    return false;
                }
            } else {
                foreach ($this->withFields as $field) {
                    if (!$field instanceof DefaultableFieldInterface) {
                        $this->stderr("$field->handle doesn’t support --to-default." . PHP_EOL, Console::FG_RED);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Re-saves categories.
     *
     * @return int
     * @deprecated in 6.0.0
     */
    public function actionCategories(): int
    {
        if (!DeprecatedConcepts::supportsCategories()) {
            $this->stderr('Categories are not supported.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $criteria = [];
        if (isset($this->group)) {
            $criteria['group'] = explode(',', $this->group);
        }

        if (!empty($this->withFields)) {
            $handles = Collection::make(Craft::$app->getCategories()->getAllGroups())
                ->filter(fn(CategoryGroup $group) => $this->hasTheFields($group->getFieldLayout()))
                ->map(fn(CategoryGroup $group) => $group->handle)
                ->all();
            if (isset($criteria['group'])) {
                $criteria['group'] = array_intersect($criteria['group'], $handles);
            } else {
                $criteria['group'] = $handles;
            }
            if (empty($criteria['group'])) {
                $this->output($this->markdownToAnsi('No category groups satisfy `--with-fields`.'));
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        return $this->resaveElements(Category::class, $criteria);
    }

    /**
     * Re-saves tags.
     *
     * @return int
     * @deprecated in 6.0.0
     */
    public function actionTags(): int
    {
        if (!DeprecatedConcepts::supportsTags()) {
            $this->stderr('Tags are not supported.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $criteria = [];
        if (isset($this->group)) {
            $criteria['group'] = explode(',', $this->group);
        }

        if (!empty($this->withFields)) {
            $handles = Collection::make(Craft::$app->getTags()->getAllTagGroups())
                ->filter(fn(TagGroup $group) => $this->hasTheFields($group->getFieldLayout()))
                ->map(fn(TagGroup $group) => $group->handle)
                ->all();
            if (isset($criteria['group'])) {
                $criteria['group'] = array_intersect($criteria['group'], $handles);
            } else {
                $criteria['group'] = $handles;
            }
            if (empty($criteria['group'])) {
                $this->output($this->markdownToAnsi('No tag groups satisfy `--with-fields`.'));
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        return $this->resaveElements(Tag::class, $criteria);
    }

    public function hasTheFields(FieldLayout $fieldLayout): bool
    {
        foreach ($this->withFields as $field) {
            if ($fieldLayout->getFieldByUid($field->uid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param class-string<ElementInterface> $elementType The element type that should be resaved
     * @param array $criteria The element criteria that determines which elements should be resaved
     * @return int
     * @since 3.7.0
     */
    public function resaveElements(string $elementType, array $criteria = []): int
    {
        $criteria += $this->_baseCriteria();

        if ($this->queue) {
            dispatch(new ResaveElements(
                elementType: $elementType,
                criteria: $criteria,
                withFields: array_map(fn(FieldInterface $field) => $field->handle, $this->withFields),
                updateSearchIndex: $this->updateSearchIndex,
                set: $this->set,
                to: $this->to,
                toDefault: $this->toDefault,
                ifEmpty: $this->ifEmpty,
                ifInvalid: $this->ifInvalid,
                touch: $this->touch,
                batchSize: $this->batchSize,
            ));

            $this->output($elementType::pluralDisplayName() . ' queued to be resaved.');
            return ExitCode::OK;
        }

        $query = $elementType::find();
        Typecast::configure($query, $criteria);
        return $this->_resaveElements($query);
    }

    /**
     * @param ElementQueryInterface $query
     * @return int
     * @since 3.2.0
     * @deprecated in 3.7.0. Use [[resaveElements()]] instead.
     */
    public function saveElements(ElementQueryInterface $query): int
    {
        if ($this->queue) {
            $this->stderr('This command doesn’t support the --queue option yet.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        Typecast::configure($query, $this->_baseCriteria());
        return $this->_resaveElements($query);
    }

    public static function registerEvents(): void
    {
        Event::listen(ElementResaveCommandsResolving::class, function(ElementResaveCommandsResolving $event) {
            if (DeprecatedConcepts::supportsCategories()) {
                $event->commands['craft:resave:categories'] = [
                    'description' => 'Re-saves categories.',
                ];
            }

            if (DeprecatedConcepts::supportsTags()) {
                $event->commands['craft:resave:tags'] = [
                    'description' => 'Re-saves tags.',
                ];
            }

            if (!YiiEvent::hasHandlers(self::class, Controller::EVENT_DEFINE_ACTIONS)) {
                return;
            }

            $yiiEvent = new DefineConsoleActionsEvent();
            YiiEvent::trigger(self::class, Controller::EVENT_DEFINE_ACTIONS, $yiiEvent);

            foreach ($yiiEvent->actions as $id => $action) {
                if (!isset($action['action'])) {
                    continue;
                }

                $event->commands["craft:resave:$id"] = [
                    'description' => $action['helpSummary'] ?? '',
                ];
            }
        });
    }

    /**
     * @return array
     */
    private function _baseCriteria(): array
    {
        $criteria = [
            'drafts' => $this->drafts,
            'provisionalDrafts' => $this->provisionalDrafts,
            'revisions' => $this->revisions,
        ];

        if ($this->provisionalDrafts !== false && $this->drafts == false) {
            $criteria['drafts'] = true;
        }

        if ($this->elementId) {
            $criteria['id'] = is_int($this->elementId) ? $this->elementId : explode(',', $this->elementId);
        }

        if ($this->uid) {
            $criteria['uid'] = explode(',', $this->uid);
        }

        if ($this->site) {
            $criteria['site'] = $this->site;
        }

        if ($this->status === 'any') {
            $criteria['status'] = null;
        } elseif ($this->status) {
            $criteria['status'] = explode(',', $this->status);
        }

        if (isset($this->offset)) {
            $criteria['offset'] = $this->offset;
        }

        if (isset($this->limit)) {
            $criteria['limit'] = $this->limit;
        }

        return $criteria;
    }

    /**
     * Resave elements
     */
    private function _resaveElements(ElementQueryInterface $query): int
    {
        /** @var ElementQuery $query */
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $query->elementType;
        $count = $query->count();

        if ($count === 0) {
            $this->output('No ' . $elementType::pluralLowerDisplayName() . ' exist for that criteria.', Console::FG_YELLOW);
            return ExitCode::OK;
        }

        if ($query->offset) {
            $count = max($count - (int)$query->offset, 0);
        }

        if ($query->limit) {
            $count = min($count, (int)$query->limit);
        }

        $to = isset($this->set) ? self::normalizeTo($this->to) : null;

        $label = isset($this->propagateTo) ? 'Propagating' : 'Resaving';
        $elementsText = $count === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName();
        $this->output("$label $count $elementsText ...", Console::FG_YELLOW);

        $elementsService = Craft::$app->getElements();
        $fail = false;

        $beforeCallback = function(MultiElementActionEvent $e) use ($query, $count, $to) {
            if ($e->query === $query) {
                $label = isset($this->propagateTo) ? 'Propagating' : 'Resaving';
                $element = $e->element;
                $this->stdout(Console::indentStr() . "    - [$e->position/$count] $label $element ($element->id) ... ");

                if (isset($this->propagateTo)) {
                    // Set the full array for all sites, so the propagated element gets the right status
                    $siteStatuses = ElementHelper::siteStatusesForElement($element);
                    foreach ($this->propagateTo as $siteId) {
                        $siteStatuses[$siteId] = $this->setEnabledForSite ?? $siteStatuses[$siteId] ?? $element->getEnabledForSite();
                    }
                    $element->setEnabledForSite($siteStatuses);
                } else {
                    if (isset($this->setEnabledForSite)) {
                        // Just set it for this site
                        $element->setEnabledForSite($this->setEnabledForSite);
                    }

                    try {
                        if ($this->toDefault) {
                            if ($this->set) {
                                $fields = [$element->getFieldLayout()?->getFieldByHandle($this->set)];
                            } else {
                                $fields = array_map(
                                    fn(FieldInterface $field) => $element->getFieldLayout()?->getFieldByUid($field->uid),
                                    $this->withFields,
                                );
                            }

                            $fields = array_filter($fields, fn(?FieldInterface $field) => $field instanceof DefaultableFieldInterface);

                            foreach ($fields as $field) {
                                $set = true;
                                if ($this->ifEmpty) {
                                    if (!ElementHelper::isAttributeEmpty($element, $field->handle)) {
                                        $set = false;
                                    }
                                } elseif ($this->ifInvalid) {
                                    $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
                                    if ($element->validate("field:$field->handle")) {
                                        $set = false;
                                    }
                                }

                                if ($set) {
                                    /** @var DefaultableFieldInterface $field */
                                    $element->setFieldValue($field->handle, $field->getDefaultValue());
                                }
                            }
                        } elseif (isset($this->set)) {
                            $set = true;
                            if ($this->ifEmpty) {
                                if (!ElementHelper::isAttributeEmpty($element, $this->set)) {
                                    $set = false;
                                }
                            } elseif ($this->ifInvalid) {
                                $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
                                if ($element->validate($this->set) && $element->validate("field:$this->set")) {
                                    $set = false;
                                }
                            }

                            if ($set) {
                                $element->{$this->set} = $to($element);
                            }
                        }
                    } catch (Throwable $e) {
                        throw new InvalidElementException($element, $e->getMessage());
                    }
                }
            }
        };

        $afterCallback = function(MultiElementActionEvent $e) use ($query, &$fail) {
            if ($e->query === $query) {
                $element = $e->element;
                if ($e->exception) {
                    $this->stdout('error: ' . $e->exception->getMessage() . PHP_EOL, Console::FG_RED);
                    $fail = true;
                } elseif ($element->errors()->isNotEmpty()) {
                    $this->stdout('failed: ' . implode(', ', $element->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
                    $fail = true;
                } else {
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                }
            }
        };


        if (isset($this->propagateTo)) {
            $elementsService->on(Elements::EVENT_BEFORE_PROPAGATE_ELEMENT, $beforeCallback);
            $elementsService->on(Elements::EVENT_AFTER_PROPAGATE_ELEMENT, $afterCallback);
            $elementsService->propagateElements($query, $this->propagateTo, true);
            $elementsService->off(Elements::EVENT_BEFORE_PROPAGATE_ELEMENT, $beforeCallback);
            $elementsService->off(Elements::EVENT_AFTER_PROPAGATE_ELEMENT, $afterCallback);
        } else {
            $elementsService->on(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
            $elementsService->on(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);
            $elementsService->resaveElements($query, true, $this->revisions === false, $this->updateSearchIndex, $this->touch);
            $elementsService->off(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
            $elementsService->off(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);
        }

        $label = isset($this->propagateTo) ? 'propagating' : 'resaving';
        $this->output("Done $label $elementsText.", Console::FG_YELLOW);
        return $fail ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
