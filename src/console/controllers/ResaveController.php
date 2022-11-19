<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\console\Controller;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\events\BatchElementActionEvent;
use craft\helpers\ElementHelper;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\queue\jobs\ResaveElements;
use craft\services\Elements;
use Throwable;
use yii\console\ExitCode;
use yii\helpers\Console;

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
            return function() {
                return null;
            };
        }

        // object template
        if (str_starts_with($to, '=')) {
            $template = substr($to, 1);
            $view = Craft::$app->getView();
            return function(ElementInterface $element) use ($template, $view) {
                return $view->renderObjectTemplate($template, $element);
            };
        }

        // PHP arrow function
        if (preg_match('/^fn\s*\(\s*(?:\$(\w+)\s*)?\)\s*=>\s*(.+)/', $to, $match)) {
            $var = $match[1];
            $php = sprintf('return %s;', StringHelper::removeLeft(rtrim($match[2], ';'), 'return '));
            return function(ElementInterface $element) use ($var, $php) {
                if ($var) {
                    $$var = $element;
                }
                return eval($php);
            };
        }

        // attribute name
        return static function(ElementInterface $element) use ($to) {
            return $element->$to;
        };
    }

    /**
     * @var bool Whether the elements should be resaved via a queue job.
     * @since 3.7.0
     */
    public bool $queue = false;

    /**
     * @var bool Whether to resave element drafts.
     * @since 3.6.5
     */
    public bool $drafts = false;

    /**
     * @var bool Whether to resave provisional element drafts.
     * @since 3.7.0
     */
    public bool $provisionalDrafts = false;

    /**
     * @var bool Whether to resave element revisions.
     * @since 3.7.35
     */
    public bool $revisions = false;

    /**
     * @var int|string|null The ID(s) of the elements to resave.
     */
    public string|int|null $elementId = null;

    /**
     * @var string|null The UUID(s) of the elements to resave.
     */
    public ?string $uid = null;

    /**
     * @var string|null The site handle to save elements from.
     */
    public ?string $site = null;

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
     * @var string|null The type handle(s) of the elements to resave.
     * @since 3.1.16
     */
    public ?string $type = null;

    /**
     * @var string|null The volume handle(s) to save assets from. Can be set to multiple comma-separated volumes.
     */
    public ?string $volume = null;

    /**
     * @var string|null The field handle to save Matrix blocks for.
     */
    public ?string $field = null;

    /**
     * @var string|null An attribute name that should be set for each of the elements. The value will be determined by --to.
     * @since 3.7.29
     */
    public ?string $set = null;

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
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'queue';
        $options[] = 'elementId';
        $options[] = 'uid';
        $options[] = 'site';
        $options[] = 'status';
        $options[] = 'offset';
        $options[] = 'limit';
        $options[] = 'updateSearchIndex';
        $options[] = 'touch';

        switch ($actionID) {
            case 'assets':
                $options[] = 'volume';
                break;
            case 'tags':
            case 'users':
            case 'categories':
                $options[] = 'group';
                break;
            case 'entries':
                $options[] = 'section';
                $options[] = 'type';
                $options[] = 'drafts';
                $options[] = 'provisionalDrafts';
                $options[] = 'revisions';
                break;
            case 'matrix-blocks':
                $options[] = 'field';
                $options[] = 'type';
                break;
        }

        $options[] = 'set';
        $options[] = 'to';
        $options[] = 'ifEmpty';

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

        if (isset($this->set) && !isset($this->to)) {
            $this->stderr('--to is required when using --set.' . PHP_EOL, Console::FG_RED);
            return false;
        }

        return true;
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
        if (isset($this->section)) {
            $criteria['section'] = explode(',', $this->section);
        }
        if (isset($this->type)) {
            $criteria['type'] = explode(',', $this->type);
        }
        return $this->resaveElements(Entry::class, $criteria);
    }

    /**
     * Re-saves Matrix blocks.
     *
     * You must supply the `--field` or `--element-id` argument for this to work properly.
     *
     * @return int
     * @since 3.2.0
     */
    public function actionMatrixBlocks(): int
    {
        $criteria = [];
        if (isset($this->field)) {
            $criteria['field'] = explode(',', $this->field);
        }
        if (isset($this->type)) {
            $criteria['type'] = explode(',', $this->type);
        }
        return $this->resaveElements(MatrixBlock::class, $criteria);
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
        return $this->resaveElements(User::class, $criteria);
    }

    /**
     * @param string $elementType The element type that should be resaved
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param array $criteria The element criteria that determines which elements should be resaved
     * @return int
     * @since 3.7.0
     */
    public function resaveElements(string $elementType, array $criteria = []): int
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $criteria += $this->_baseCriteria();

        if ($this->queue) {
            Queue::push(new ResaveElements([
                'elementType' => $elementType,
                'criteria' => $criteria,
                'set' => $this->set,
                'to' => $this->to,
                'ifEmpty' => $this->ifEmpty,
                'touch' => $this->touch,
                'updateSearchIndex' => $this->updateSearchIndex,
            ]));
            $this->stdout($elementType::pluralDisplayName() . ' queued to be resaved.' . PHP_EOL);
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
        $criteria = [];

        if ($this->drafts) {
            $criteria['drafts'] = true;
        }

        if ($this->provisionalDrafts) {
            $criteria['drafts'] = true;
            $criteria['provisionalDrafts'] = true;
        }

        if ($this->revisions) {
            $criteria['revisions'] = true;
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
        /** @var ElementInterface $elementType */
        $elementType = $query->elementType;
        $count = (int)$query->count();

        if ($count === 0) {
            $this->stdout('No ' . $elementType::pluralLowerDisplayName() . ' exist for that criteria.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        if ($query->limit) {
            $count = min($count, (int)$query->limit);
        }

        $to = isset($this->set) ? self::normalizeTo($this->to) : null;

        $elementsText = $count === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName();
        $this->stdout("Resaving $count $elementsText ..." . PHP_EOL, Console::FG_YELLOW);

        $elementsService = Craft::$app->getElements();
        $fail = false;

        $beforeCallback = function(BatchElementActionEvent $e) use ($query, $count, $to) {
            if ($e->query === $query) {
                $element = $e->element;
                $this->stdout("    - [$e->position/$count] Resaving $element ($element->id) ... ");

                try {
                    if (isset($this->set) && (!$this->ifEmpty || ElementHelper::isAttributeEmpty($element, $this->set))) {
                        $element->{$this->set} = $to($element);
                    }
                } catch (Throwable $e) {
                    throw new InvalidElementException($element, $e->getMessage());
                }
            }
        };

        $afterCallback = function(BatchElementActionEvent $e) use ($query, &$fail) {
            if ($e->query === $query) {
                $element = $e->element;
                if ($e->exception) {
                    $this->stderr('error: ' . $e->exception->getMessage() . PHP_EOL, Console::FG_RED);
                    $fail = true;
                } elseif ($element->hasErrors()) {
                    $this->stderr('failed: ' . implode(', ', $element->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
                    $fail = true;
                } else {
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                }
            }
        };

        $elementsService->on(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
        $elementsService->on(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);

        $elementsService->resaveElements($query, true, !$this->revisions, $this->updateSearchIndex, $this->touch);

        $elementsService->off(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
        $elementsService->off(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);

        $this->stdout("Done resaving $elementsText." . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
        return $fail ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
