<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\console\Controller;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\events\MultiElementActionEvent;
use craft\helpers\App;
use craft\helpers\Console;
use craft\helpers\ElementHelper;
use craft\helpers\Inflector;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\Site;
use craft\models\TagGroup;
use craft\models\Volume;
use craft\queue\jobs\ResaveElements;
use craft\services\Elements;
use Illuminate\Support\Collection;
use ReflectionClass;
use Throwable;
use yii\console\Exception;
use yii\console\ExitCode;

/**
 * Allows you to bulk-save elements.
 *
 * See [Bulk-Resaving Elements](https://craftcms.com/knowledge-base/bulk-resaving-elements) for examples.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.15
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
        // empty
        if ($to === ':empty:') {
            return fn() => '';
        }

        // object template
        if (str_starts_with($to, '=')) {
            $template = substr($to, 1);
            $view = Craft::$app->getView();
            return fn(ElementInterface $element) => $view->renderObjectTemplate($template, $element);
        }

        // PHP arrow function
        if (preg_match('/^fn\s*\(\s*(?:\$(\w+)\s*)?\)\s*=>\s*(.+)/', $to, $match)) {
            $var = $match[1];
            $php = sprintf('return %s;', StringHelper::removeLeft(rtrim($match[2], ';'), 'return '));
            return function(ElementInterface $element) use ($var, $php) {
                if ($var) {
                    ${$var} = $element;
                }
                return eval($php);
            };
        }

        // attribute name
        return static fn(ElementInterface $element) => $element->$to;
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
     * @var string[] Only resave elements that have custom fields with these global field handles.
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
                $value = App::normalizeValue($this->$property);
                $this->$property = $value !== null ? (bool)$value : null;
            }
        }

        if (isset($this->propagateTo)) {
            $siteHandles = array_filter(StringHelper::split($this->propagateTo));
            $this->propagateTo = [];
            $sitesService = Craft::$app->getSites();
            foreach ($siteHandles as $siteHandle) {
                $site = $sitesService->getSiteByHandle($siteHandle, true);
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

        if (isset($this->set) && !isset($this->to)) {
            $this->stderr('--to is required when using --set.' . PHP_EOL, Console::FG_RED);
            return false;
        }

        return true;
    }

    /**
     * Runs all other `resave/*` commands.
     *
     * @return int
     */
    public function actionAll(): int
    {
        $actions = [];
        $ref = new ReflectionClass($this);
        foreach ($ref->getMethods() as $method) {
            if (
                $method->name !== 'actionAll' &&
                $method->isPublic() &&
                !$method->isStatic() &&
                !$method->isAbstract() &&
                $method->getDeclaringClass()->name === self::class &&
                str_starts_with($method->name, 'action')
            ) {
                $actions[] = StringHelper::toKebabCase(substr($method->name, 6));
            }
        }
        array_push($actions, ...array_keys($this->actions()));

        $params = $this->getPassedOptionValues();
        $actionsToSkip = [];

        // check if all actions support all the params
        foreach ($actions as $key => $id) {
            if (!$this->doesActionSupportsAllOptions($id, $params)) {
                $actionsToSkip[] = $id;
                unset($actions[$key]);
            }
        }

        // ask for confirmation
        if ($this->interactive && !empty($actionsToSkip)) {
            $this->output('The following commands don’t support the provided options, and will be skipped:', Console::FG_YELLOW);
            foreach ($actionsToSkip as $id) {
                $invalidParams = array_map(
                    fn($param) => sprintf('`--%s`', StringHelper::toKebabCase($param)),
                    $this->getUnsupportedOptions($id, $params)
                );
                $this->output(' ' . $this->markdownToAnsi(sprintf(
                    '- `resave/%s` doesn’t support %s',
                    $id,
                    Inflector::sentence($invalidParams)
                )));
            }
            Console::outdent();
            if (!$this->confirm('Continue?', true)) {
                return ExitCode::OK;
            }
        }

        // run the actions which support all the params
        foreach ($actions as $id) {
            try {
                $this->output();
                $this->do("Running `resave/$id`", function() use ($id, $params) {
                    Console::indent();
                    try {
                        $this->runAction($id, $params);
                    } finally {
                        Console::outdent();
                    }
                });
            } catch (Exception) {
            }
        }

        return ExitCode::OK;
    }

    /**
     * Re-saves user addresses.
     *
     * @return int
     * @since 4.5.6
     */
    public function actionAddresses(): int
    {
        $criteria = [];
        if (isset($this->ownerId)) {
            $criteria['ownerId'] = array_map(fn(string $id) => (int)$id, explode(',', (string)$this->ownerId));
        }
        if (isset($this->countryCode)) {
            $criteria['countryCode'] = explode(',', (string)$this->countryCode);
        }

        if (!empty($this->withFields)) {
            $fieldLayout = Craft::$app->getAddresses()->getFieldLayout();
            if (!$this->hasTheFields($fieldLayout)) {
                $this->output($this->markdownToAnsi('The address field layout doesn’t satisfy `--with-fields`.'));
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        return $this->resaveElements(Address::class, $criteria);
    }

    /**
     * Re-saves assets.
     *
     * @return int
     */
    public function actionAssets(): int
    {
        $criteria = [];
        if (isset($this->volume)) {
            $criteria['volume'] = explode(',', $this->volume);
        }

        if (!empty($this->withFields)) {
            $handles = Collection::make(Craft::$app->getVolumes()->getAllVolumes())
                ->filter(fn(Volume $volume) => $this->hasTheFields($volume->getFieldLayout()))
                ->map(fn(Volume $volume) => $volume->handle)
                ->all();
            if (isset($criteria['volume'])) {
                $criteria['volume'] = array_intersect($criteria['volume'], $handles);
            } else {
                $criteria['volume'] = $handles;
            }
            if (empty($criteria['volume'])) {
                $this->output($this->markdownToAnsi('No volumes satisfy `--with-fields`.'));
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        return $this->resaveElements(Asset::class, $criteria);
    }

    /**
     * Re-saves categories.
     *
     * @return int
     */
    public function actionCategories(): int
    {
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
     * Re-saves entries.
     *
     * @return int
     */
    public function actionEntries(): int
    {
        $criteria = [];
        if ($this->allSections) {
            $criteria['section'] = '*';
        } elseif (isset($this->section)) {
            $criteria['section'] = explode(',', $this->section);
        }
        if (isset($this->field)) {
            $criteria['field'] = explode(',', $this->field);
        }
        if (isset($this->ownerId)) {
            $criteria['ownerId'] = array_map(fn(string $id) => (int)$id, explode(',', (string)$this->ownerId));
        }
        if (isset($this->type)) {
            $criteria['type'] = explode(',', $this->type);
        }

        if (!empty($this->withFields)) {
            $handles = Collection::make(Craft::$app->getEntries()->getAllEntryTypes())
                ->filter(fn(EntryType $entryType) => $this->hasTheFields($entryType->getFieldLayout()))
                ->map(fn(EntryType $entryType) => $entryType->handle)
                ->all();
            if (isset($criteria['type'])) {
                $criteria['type'] = array_intersect($criteria['type'], $handles);
            } else {
                $criteria['type'] = $handles;
            }
            if (empty($criteria['type'])) {
                $this->output($this->markdownToAnsi('No entry types satisfy `--with-fields`.'));
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        return $this->resaveElements(Entry::class, $criteria);
    }

    /**
     * Re-saves tags.
     *
     * @return int
     */
    public function actionTags(): int
    {
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

    /**
     * Re-saves users.
     *
     * @return int
     */
    public function actionUsers(): int
    {
        $criteria = [];
        if (isset($this->group)) {
            $criteria['group'] = explode(',', $this->group);
        }

        if (!empty($this->withFields)) {
            $fieldLayout = Craft::$app->getFields()->getLayoutByType(User::class);
            if (!$this->hasTheFields($fieldLayout)) {
                $this->output($this->markdownToAnsi('The user field layout doesn’t satisfy `--with-fields`.'));
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        return $this->resaveElements(User::class, $criteria);
    }

    /**
     * Returns whether a field layout has any of the fields specified by [[$withFields]].
     * @param FieldLayout $fieldLayout
     * @return bool
     * @since 5.5.0
     */
    public function hasTheFields(FieldLayout $fieldLayout): bool
    {
        $fieldsService = Craft::$app->getFields();
        foreach ($this->withFields as $handle) {
            $field = $fieldsService->getFieldByHandle($handle);
            if ($field && $fieldLayout->getFieldByUid($field->uid)) {
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
            Queue::push(new ResaveElements([
                'elementType' => $elementType,
                'criteria' => $criteria,
                'set' => $this->set,
                'to' => $this->to,
                'ifEmpty' => $this->ifEmpty,
                'ifInvalid' => $this->ifInvalid,
                'touch' => $this->touch,
                'updateSearchIndex' => $this->updateSearchIndex,
                'batchSize' => $this->batchSize,
            ]));
            $this->output($elementType::pluralDisplayName() . ' queued to be resaved.');
            return ExitCode::OK;
        }

        $query = $elementType::find();
        Craft::configure($query, $criteria);
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

        Craft::configure($query, $this->_baseCriteria());
        return $this->_resaveElements($query);
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
        $count = (int)$query->count();

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
                        if (isset($this->set)) {
                            $set = true;
                            if ($this->ifEmpty) {
                                if (!ElementHelper::isAttributeEmpty($element, $this->set)) {
                                    $set = false;
                                }
                            } elseif ($this->ifInvalid) {
                                $element->setScenario(Element::SCENARIO_LIVE);
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
                } elseif ($element->hasErrors()) {
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

    /**
     * Returns whether all options passed to an action are supported.
     * Used by resave/all command.
     *
     * @param string $actionId
     * @param array $params
     * @return bool
     */
    private function doesActionSupportsAllOptions(string $actionId, array $params): bool
    {
        $options = $this->options($actionId);
        foreach ($params as $param => $value) {
            if (!in_array($param, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns an array of options that are not supported by the action.
     *
     * @param string $actionId
     * @param array $params
     * @return array
     */
    private function getUnsupportedOptions(string $actionId, array $params): array
    {
        $unsupportedParams = [];
        $options = $this->options($actionId);
        foreach ($params as $param => $value) {
            if (!in_array($param, $options)) {
                $unsupportedParams[] = $param;
            }
        }

        return $unsupportedParams;
    }
}
