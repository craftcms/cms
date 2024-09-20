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
use craft\elements\Entry;
use craft\helpers\Console;
use yii\console\ExitCode;

/**
 * Prunes orphaned entries for each site.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class PruneOrphanedEntriesController extends Controller
{
    /**
     * Prunes orphaned entries for each site.
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

        // for each site get all nested entries with owner that doesn't exist for this site
        foreach ($sites as $site) {
            $this->stdout(sprintf('Finding orphaned entries for site "%s" ... ', $site->getName()));

            $esSubQuery = (new Query())
                ->from(['es' => Table::ELEMENTS_SITES])
                ->where([
                    'and',
                    '[[es.elementId]] = [[entries.primaryOwnerId]]',
                    ['es.siteId' => $site->id],
                ]);

            $entries = Entry::find()
                ->status(null)
                ->siteId($site->id)
                ->where(['not', ['entries.primaryOwnerId' => null]])
                ->andWhere(['not exists', $esSubQuery])
                ->all();

            if (empty($entries)) {
                $this->stdout("none found\n", Console::FG_GREEN);
                continue;
            }

            $this->stdout(sprintf("%s found\n", count($entries)), Console::FG_RED);

            // delete the ones we found
            foreach ($entries as $entry) {
                $this->do(sprintf('Deleting entry %s in %s', $entry->id, $site->getName()), function() use ($entry, $elementsService) {
                    $elementsService->deleteElementForSite($entry);
                });
            }
        }

        $this->stdout("\nFinished pruning orphaned entries.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
