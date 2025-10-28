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
use craft\helpers\Console;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use yii\console\ExitCode;

/**
 * Prunes provisional drafts for elements that have more than one per user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.9
 */
class PruneProvisionalDraftsController extends Controller
{
    /**
     * @var bool Whether this is a dry run.
     */
    public bool $dryRun = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'dryRun';
        return $options;
    }

    /**
     * Prunes provisional drafts for elements that have more than one per user.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->stdout('Finding elements with multiple provisional drafts per user ... ');

        $elements = DB::table(
            table: DB::table(Table::DRAFTS)
                ->select(['canonicalId', 'creatorId', DB::raw('COUNT(*) as count')])
                ->where('provisional', true)
                ->groupBy(['canonicalId', 'creatorId'])
                ->havingRaw('COUNT(*) > 1'),
            as: 's',
        )->select([
            's.canonicalId as id',
            's.creatorId',
            's.count',
            'type' => DB::table(Table::ELEMENTS)
                ->select('type')
                ->whereColumn('id', 's.canonicalId'),
        ])->get();

        $this->stdout('done' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        if ($elements->isEmpty()) {
            $this->stdout('Nothing to prune' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('Pruning extra provisional drafts ...' . PHP_EOL);

        $elementsService = Craft::$app->getElements();

        foreach ($elements as $element) {
            if (!class_exists($element->type)) {
                continue;
            }

            /** @var class-string<ElementInterface> $elementType */
            $elementType = $element->type;
            $deleteCount = $element->count - 1;

            $this->stdout('- ' . $elementType::displayName() . " {$element->id} for user {$element->creatorId} ($deleteCount provisional drafts) ... ");

            $extraDrafts = $elementType::find()
                ->provisionalDrafts()
                ->draftOf($element->id)
                ->draftCreator($element->draftCreatorId)
                ->site('*')
                ->unique()
                ->status(null)
                ->orderBy(['dateUpdated' => SORT_DESC])
                ->offset(1)
                ->all();

            if (!$this->dryRun) {
                foreach ($extraDrafts as $extraDraft) {
                    $elementsService->deleteElement($extraDraft, true);
                }
            }

            $this->stdout('done', Console::FG_GREEN);

            if (count($extraDrafts) !== $deleteCount) {
                $this->stdout(' (found ' . count($extraDrafts) . ')', Console::FG_RED);
            }

            $this->stdout(PHP_EOL);
        }

        $this->stdout(PHP_EOL . 'Finished pruning extra provisional drafts' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
