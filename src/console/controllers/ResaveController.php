<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Tag;
use craft\elements\User;
use craft\helpers\App;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\console\Controller;
use craft\elements\Entry;
use craft\elements\Category;

/**
 * Bulk-saves elements
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
     * @var int The batch size to query elements in.
     */
    public $batchSize = 100;

    /**
     * @var bool Whether to save the elements across all their enabled sites.
     */
    public $propagate = true;

    /**
     * @var string|null The group handle(s) to save categories/tags/users from. Can be set to multiple comma-separated groups.
     */
    public $group;

    /**
     * @var string|null The section handle(s) to save entries from. Can be set to multiple comma-separated sections.
     */
    public $section;

    /**
     * @var string|null The entry type handle(s) of the entries to resave.
     */
    public $type;

    /**
     * @var string|null The volume handle(s) to save assets from. Can be set to multiple comma-separated volumes.
     */
    public $volume;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'id';
        $options[] = 'uid';
        $options[] = 'site';
        $options[] = 'status';
        $options[] = 'offset';
        $options[] = 'limit';
        $options[] = 'batchSize';
        $options[] = 'propagate';

        switch ($actionID) {
            case 'assets':
                $options[] = 'volume';
                break;
            case 'categories':
                $options[] = 'group';
                break;
            case 'entries':
                $options[] = 'section';
                $options[] = 'type';
                break;
            case 'tags':
                $options[] = 'group';
                break;
            case 'users':
                $options[] = 'group';
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
        return $this->_saveElements($query);
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
        return $this->_saveElements($query);
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
        return $this->_saveElements($query);
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
        return $this->_saveElements($query);
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
        return $this->_saveElements($query);
    }

    /**
     * @param ElementQueryInterface $query
     * @return int
     */
    private function _saveElements(ElementQueryInterface $query): int
    {
        /** @var ElementQuery $query */
        $type = App::humanizeClass($query->elementType);

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
            $this->stdout("No {$type} elements exist for that criteria." . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $elementsText = $count === 1 ? 'element' : 'elements';
        $this->stdout("Resaving {$count} {$type} {$elementsText} ..." . PHP_EOL, Console::FG_YELLOW);

        $elementsService = Craft::$app->getElements();
        $fail = false;

        foreach ($query->each($this->batchSize) as $element) {
            /** @var Element $element */
            $this->stdout("    - Resaving {$element} ({$element->id}) ... ");
            $element->setScenario(Element::SCENARIO_ESSENTIALS);
            try {
                if (!$elementsService->saveElement($element)) {
                    $this->stderr('failed: ' . implode(', ', $element->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
                    $fail = true;
                    continue;
                }
            } catch (\Throwable $e) {
                Craft::$app->getErrorHandler()->logException($e);
                $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                $fail = true;
                continue;
            }

            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout("Done resaving {$type} elements." . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
        return $fail ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
