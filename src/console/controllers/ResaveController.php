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
use craft\events\BatchElementActionEvent;
use craft\services\Elements;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Allows you to bulk-saves elements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.15
 */
class ResaveController extends Controller
{
    /**
     * @var int|string The ID(s) of the elements to resave.
     */
    public $elementId;

    /**
     * @var string The UUID(s) of the elements to resave.
     */
    public $uid;

    /**
     * @var string|null The site handle to save elements from.
     */
    public $site;

    /**
     * @var string The status(es) of elements to resave. Can be set to multiple comma-separated statuses.
     */
    public $status = 'any';

    /**
     * @var int|null The number of elements to skip.
     */
    public $offset;

    /**
     * @var int|null The number of elements to resave.
     */
    public $limit;

    /**
     * @var bool Whether to save the elements across all their enabled sites.
     */
    public $propagate = true;

    /**
     * @var bool Whether to update the search indexes for the resaved elements.
     */
    public $updateSearchIndex = false;

    /**
     * @var string|null The group handle(s) to save categories/tags/users from. Can be set to multiple comma-separated groups.
     */
    public $group;

    /**
     * @var string|null The section handle(s) to save entries from. Can be set to multiple comma-separated sections.
     */
    public $section;

    /**
     * @var string|null The type handle(s) of the elements to resave.
     * @since 3.1.16
     */
    public $type;

    /**
     * @var string|null The volume handle(s) to save assets from. Can be set to multiple comma-separated volumes.
     */
    public $volume;

    /**
     * @var string|null The field handle to save Matrix blocks for.
     */
    public $field;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'elementId';
        $options[] = 'uid';
        $options[] = 'site';
        $options[] = 'status';
        $options[] = 'offset';
        $options[] = 'limit';
        $options[] = 'propagate';
        $options[] = 'updateSearchIndex';

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
                break;
            case 'matrix-blocks':
                $options[] = 'field';
                $options[] = 'type';
                break;
        }

        return $options;
    }

    /**
     * Re-saves assets.
     *
     * @return int
     */
    public function actionAssets(): int
    {
        $query = Asset::find();
        if ($this->volume !== null) {
            $query->volume(explode(',', $this->volume));
        }
        return $this->saveElements($query);
    }

    /**
     * Re-saves categories.
     *
     * @return int
     */
    public function actionCategories(): int
    {
        $query = Category::find();
        if ($this->group !== null) {
            $query->group(explode(',', $this->group));
        }
        return $this->saveElements($query);
    }

    /**
     * Re-saves entries.
     *
     * @return int
     */
    public function actionEntries(): int
    {
        $query = Entry::find();
        if ($this->section !== null) {
            $query->section(explode(',', $this->section));
        }
        if ($this->type !== null) {
            $query->type(explode(',', $this->type));
        }
        return $this->saveElements($query);
    }

    /**
     * Re-saves Matrix blocks.
     *
     * Note that you must supply the --field or --element-id argument for this to work properly.
     *
     * @return int
     * @since 3.2.0
     */
    public function actionMatrixBlocks(): int
    {
        $query = MatrixBlock::find();
        if ($this->field !== null) {
            $query->field(explode(',', $this->field));
        }
        if ($this->type !== null) {
            $query->type(explode(',', $this->type));
        }
        return $this->saveElements($query);
    }

    /**
     * Re-saves tags.
     *
     * @return int
     */
    public function actionTags(): int
    {
        $query = Tag::find();
        if ($this->group !== null) {
            $query->group(explode(',', $this->group));
        }
        return $this->saveElements($query);
    }

    /**
     * Re-saves users.
     *
     * @return int
     */
    public function actionUsers(): int
    {
        $query = User::find();
        if ($this->group !== null) {
            $query->group(explode(',', $this->group));
        }
        return $this->saveElements($query);
    }

    /**
     * @param ElementQueryInterface $query
     * @return int
     * @since 3.2.0
     */
    public function saveElements(ElementQueryInterface $query): int
    {
        /** @var ElementQuery $query */
        /** @var ElementInterface $elementType */
        $elementType = $query->elementType;

        if ($this->elementId) {
            $query->id(is_int($this->elementId) ? $this->elementId : explode(',', $this->elementId));
        }

        if ($this->uid) {
            $query->uid(explode(',', $this->uid));
        }

        if ($this->site) {
            $query->site($this->site);
        }

        if ($this->status === 'any') {
            $query->anyStatus();
        } else if ($this->status) {
            $query->status(explode(',', $this->status));
        }

        if ($this->offset !== null) {
            $query->offset($this->offset);
        }

        if ($this->limit !== null) {
            $query->limit($this->limit);
        }

        $count = (int)$query->count();

        if ($count === 0) {
            $this->stdout('No ' . $elementType::pluralLowerDisplayName() . ' exist for that criteria.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        if ($query->limit) {
            $count = min($count, (int)$query->limit);
        }

        $elementsText = $count === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName();
        $this->stdout("Resaving {$count} {$elementsText} ..." . PHP_EOL, Console::FG_YELLOW);

        $elementsService = Craft::$app->getElements();
        $fail = false;

        $beforeCallback = function(BatchElementActionEvent $e) use ($query) {
            if ($e->query === $query) {
                $element = $e->element;
                $this->stdout("    - Resaving {$element} ({$element->id}) ... ");
            }
        };

        $afterCallback = function(BatchElementActionEvent $e) use ($query, &$fail) {
            if ($e->query === $query) {
                $element = $e->element;
                if ($e->exception) {
                    $this->stderr('error: ' . $e->exception->getMessage() . PHP_EOL, Console::FG_RED);
                    $fail = true;
                } else if ($element->hasErrors()) {
                    $this->stderr('failed: ' . implode(', ', $element->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
                    $fail = true;
                } else {
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                }
            }
        };

        $elementsService->on(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
        $elementsService->on(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);

        $elementsService->resaveElements($query, true, true, $this->updateSearchIndex);

        $elementsService->off(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
        $elementsService->off(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);

        $this->stdout("Done resaving {$elementsText}." . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
        return $fail ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
