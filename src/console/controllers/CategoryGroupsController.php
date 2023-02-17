<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Manages category groups.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class CategoryGroupsController extends Controller
{
    /**
     * Deletes a category group.
     *
     * @param string $handle The category group handle
     * @return int
     */
    public function actionDelete(string $handle): int
    {
        $categoriesService = Craft::$app->getCategories();
        $group = $categoriesService->getGroupByHandle($handle);

        if (!$group) {
            $this->stderr("Invalid category group handle: $handle\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->interactive && !$this->confirm("Are you sure you want to delete the category group “{$group->name}”?")) {
            $this->stdout("Aborted.\n");
            return ExitCode::OK;
        }

        $this->do('Deleting category group', function() use ($categoriesService, $group) {
            if (!$categoriesService->deleteGroup($group)) {
                $message = ArrayHelper::firstValue($group->getFirstErrors()) ?? 'Unable to delete the category group.';
                throw new InvalidConfigException($message);
            }
        });

        $this->success('Category group deleted.');
        return ExitCode::OK;
    }
}
