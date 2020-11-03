<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use Craft;
use craft\base\ElementInterface;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
use yii\console\ExitCode;
use yii\db\Expression;

/**
 * Prunes excess element revisions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class PruneRevisionsController extends Controller
{
    /**
     * @var int The maximum number of revisions an element can have.
     */
    public $maxRevisions;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'maxRevisions';
        return $options;
    }

    /**
     * Prunes excess element revisions.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        if ($this->maxRevisions === null) {
            $this->maxRevisions = $this->prompt('What is the max number of revisions an element can have?', [
                'default' => Craft::$app->getConfig()->getGeneral()->maxRevisions,
                'validator' => function($input) {
                    return filter_var($input, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) !== null && $input >= 0;
                }
            ]);
        }

        // Get the elements with too many revisions
        $this->stdout('Finding elements with too many revisions ... ');
        $elements = (new Query())
            ->select([
                'id' => 's.sourceId',
                's.count',
                'type' => (new Query())
                    ->select(['type'])
                    ->from([Table::ELEMENTS])
                    ->where(new Expression('[[id]] = [[s.sourceId]]'))
            ])
            ->from([
                's' => (new Query())
                    ->select(['sourceId', 'count' => 'COUNT(*)'])
                    ->from(['r' => Table::REVISIONS])
                    ->groupBy(['sourceId'])
                    ->having(['>', 'COUNT(*)', $this->maxRevisions])
            ])
            ->all();
        $this->stdout('done' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        if (empty($elements)) {
            $this->stdout('Nothing to prune' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('Pruning revisions ...' . PHP_EOL);

        $elementsService = Craft::$app->getElements();

        foreach ($elements as $element) {
            if (!class_exists($element['type'])) {
                continue;
            }

            /** @var ElementInterface|string $elementType */
            $elementType = $element['type'];
            $deleteCount = $element['count'] - $this->maxRevisions;
            $this->stdout('- ' . $elementType::displayName() . " {$element['id']} ({$deleteCount} revisions) ... ");
            $extraRevisions = $elementType::find()
                ->revisionOf($element['id'])
                ->siteId('*')
                ->unique()
                ->anyStatus()
                ->orderBy(['num' => SORT_DESC])
                ->offset($this->maxRevisions)
                ->all();
            foreach ($extraRevisions as $extraRevision) {
                $elementsService->deleteElement($extraRevision, true);
            }
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout(PHP_EOL . 'Finished pruning revisions' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
