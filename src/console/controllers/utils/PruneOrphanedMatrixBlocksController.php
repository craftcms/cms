<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\elements\MatrixBlock;
use craft\helpers\Console;
use yii\console\ExitCode;

/**
 * Prunes orphaned matrix blocks for each site.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.7.0
 */
class PruneOrphanedMatrixBlocksController extends Controller
{
    /**
     * Prunes orphaned matrix blocks for each site.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        if (Craft::$app->getIsMultiSite()) {
            // get all sites
            $sites = Craft::$app->getSites()->getAllSites();

            // for each site get all matrix blocks with owner that doesn't exist for this site
            foreach ($sites as $site) {
                $this->stdout('Finding orphaned matrix blocks for site `' . $site->getName() . '`... ' . PHP_EOL);

                $esSubQuery = (new Query())
                    ->from(['es' => Table::ELEMENTS_SITES])
                    ->where([
                        'and',
                        '[[es.elementId]] = [[matrixblocks.primaryOwnerId]]',
                        ['es.siteId' => $site->id],
                    ]);

                $matrixBlocks = MatrixBlock::find()
                    ->status(null)
                    ->siteId($site->id)
                    ->where(['not exists', $esSubQuery])
                    ->all();

                $this->stdout(' Found ' . count($matrixBlocks) . ' orphaned blocks' . PHP_EOL, Console::FG_RED);

                $elementsService = Craft::$app->getElements();

                // delete the ones we found
                foreach ($matrixBlocks as $block) {
                    $block->siteId = $site->id;
                    $block->deletedWithOwner = false;
                    $elementsService->deleteElementForSite($block);
                }
            }

            $this->stdout(PHP_EOL . 'Finished pruning orphaned matrix blocks' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        } else {
            $this->stdout('Nothing to prune' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
