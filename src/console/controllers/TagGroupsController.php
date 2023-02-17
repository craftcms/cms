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
 * Manages tag groups.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class TagGroupsController extends Controller
{
    /**
     * Deletes a tag group.
     *
     * @param string $handle The tag group handle
     * @return int
     */
    public function actionDelete(string $handle): int
    {
        $tagsService = Craft::$app->getTags();
        $group = $tagsService->getTagGroupByHandle($handle);

        if (!$group) {
            $this->stderr("Invalid tag group handle: $handle\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->interactive && !$this->confirm("Are you sure you want to delete the tag group “{$group->name}”?")) {
            $this->stdout("Aborted.\n");
            return ExitCode::OK;
        }

        $this->do('Deleting tag group', function() use ($tagsService, $group) {
            if (!$tagsService->deleteTagGroup($group)) {
                $message = ArrayHelper::firstValue($group->getFirstErrors()) ?? 'Unable to delete the tag group.';
                throw new InvalidConfigException($message);
            }
        });

        $this->success('Tag group deleted.');
        return ExitCode::OK;
    }
}
