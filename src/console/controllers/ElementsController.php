<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\elements\db\CategoryQuery;
use craft\elements\db\EntryQuery;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\console\Controller;
use craft\elements\Entry;
use craft\elements\Category;

/**
 * Bulk-save entries and categories
 */
class ElementsController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'save-all';

    /**
     * Saves all entries in a specified section ($sectionHandle, $startAt = 1, $batchSize = 50).
     *
     * @param string $sectionHandle
     * @param int $startAt
     * @param int $batchSize
     * @return int
     */
    public function actionSaveEntries($sectionHandle, $startAt = 1, $batchSize = 50): int
    {
        $query = Entry::find()->section($sectionHandle);
        return $this->_saveElements($query, $startAt, $batchSize, 'Entry');
    }

    /**
     * Saves all entries across all sections ($startAt = 1, $batchSize = 50).
     *
     * @param int $startAt
     * @param int $batchSize
     * @return int
     */
    public function actionSaveAllEntries($startAt = 1, $batchSize = 50): int
    {
        $query = Entry::find();
        return $this->_saveElements($query, $startAt, $batchSize, 'Entry');
    }

    /**
     * Saves all entries in a specified section ($groupHandle, $startAt = 1, $batchSize = 50).
     *
     * @param string $groupHandle
     * @param int $startAt
     * @param int $batchSize
     * @return int
     */
    public function actionSaveCategories($groupHandle, $startAt = 1, $batchSize = 50): int
    {
        $query = Category::find()->group($groupHandle);
        return $this->_saveElements($query, $startAt, $batchSize, 'Category');
    }

    /**
     * Saves all entries across all sections ($startAt = 1, $batchSize = 50).
     *
     * @param int $startAt
     * @param int $batchSize
     * @return int
     */
    public function actionSaveAllCategories($startAt = 1, $batchSize = 50): int
    {
        $query = Category::find();
        return $this->_saveElements($query, $startAt, $batchSize, 'Category');
    }

    /**
     * Saves all entries and all categories.
     *
     * @return int
     */
    public function actionSaveAll(): int
    {
        $this->actionSaveAllEntries();
        $this->actionSaveAllCategories();
        return ExitCode::OK;
    }

    /**
     * @param EntryQuery|CategoryQuery $query
     * @param int $startAt
     * @param int $batchSize
     * @param string $type
     * @return int
     */
    private function _saveElements($query, $startAt, $batchSize, $type)
    {
        $offset = $startAt - 1;
        $count = $query->offset($offset)->count();
        $pages = ceil($count / $batchSize);
        $page = 0;

        $this->stdout($type . ' Elements Found: ' . $count . PHP_EOL);

        while ($elements = $query->offset($offset)->limit($batchSize)->orderBy('id ASC')->all()) {

            $this->stdout('Starting batch of ' . $batchSize . ' (#' . ++$page . '/' . $pages . ')' . PHP_EOL);

            foreach ($elements as $element) {

                $paddedIndex = str_pad($startAt, strlen((string)$count), '0', STR_PAD_LEFT);

                try {
                    Craft::$app->elements->saveElement($element);
                    $this->stdout('[' . $paddedIndex . '] ' . $type . ' #' . $element->id . ' (' . $element->title . ') successfully saved!' . PHP_EOL, Console::FG_GREEN);
                } catch (\Throwable $e) {
                    $this->stdout('[' . $paddedIndex . '] ' . $type . ' #' . $element->id . ' (' . $element->title . ') error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                }

                $startAt++;
            }

            $offset += 50;
        }

        return ExitCode::OK;
    }
}
