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
 * Prunes orphaned Matrix blocks for each site.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.7.0
 */
class PruneOrphanedMatrixBlocksController extends Controller
{
    /**
     * Prunes orphaned Matrix blocks for each site.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        if (!Craft::$app->getIsMultiSite()) {
            $this->stdout("This command should only be run for multi-site installs.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $elementsService = Craft::$app->getElements();

        // get all sites
        $sites = Craft::$app->getSites()->getAllSites();

        // for each site get all Matrix blocks with owner that doesn't exist for this site
        foreach ($sites as $site) {
            $this->stdout(sprintf('Finding orphaned Matrix blocks for site "%s" ... ', $site->getName()));

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

            if (empty($matrixBlocks)) {
                $this->stdout("none found\n", Console::FG_GREEN);
                continue;
            }

            $this->stdout(sprintf("%s found\n", count($matrixBlocks)), Console::FG_RED);

            // delete the ones we found
            foreach ($matrixBlocks as $block) {
                $this->do(sprintf('Deleting block %s in %s', $block->id, $site->getName()), function() use ($block, $elementsService) {
                    $elementsService->deleteElementForSite($block);
                });
            }
        }

        $this->stdout("\nFinished pruning orphaned Matrix blocks.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
