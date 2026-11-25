<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\Entry;
use craft\events\MultiElementActionEvent;
use craft\helpers\Console;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\services\Elements;
use yii\console\ExitCode;

/**
 * Updates statically-stored entry statuses.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.7.0
 */
class UpdateStatusesController extends Controller
{
    /**
     * @inheritdoc
     */
    public bool $isolated = true;

    /**
     * Updates statically-stored entry statuses.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $now = Db::prepareDateForDb(DateTimeHelper::now());
        $elementsService = Craft::$app->getElements();

        $conditions = [
            Entry::STATUS_LIVE => [
                'and',
                ['<=', 'entries.postDate', $now],
                [
                    'or',
                    ['entries.expiryDate' => null],
                    ['>', 'entries.expiryDate', $now],
                ],
            ],
            Entry::STATUS_PENDING => ['>', 'entries.postDate', $now],
            Entry::STATUS_EXPIRED => [
                'and',
                ['not', ['entries.expiryDate' => null]],
                ['<=', 'entries.expiryDate', $now],
            ],
        ];

        foreach ($conditions as $status => $condition) {
            $query = Entry::find()
                ->site('*')
                ->unique()
                ->status(null)
                ->andWhere(['not', ['status' => $status]])
                ->andWhere($condition);

            $this->do("Updating $status entries", function() use ($elementsService, $query) {
                $count = (int)$query->count();

                $beforeCallback = function(MultiElementActionEvent $e) use ($query, $count) {
                    if ($e->query === $query) {
                        $this->stdout(Console::indentStr() . "    - [$e->position/$count] Updating entry ({$e->element->id}) ... ");
                    }
                };

                $afterCallback = function(MultiElementActionEvent $e) use ($query, &$fail) {
                    if ($e->query === $query) {
                        if ($e->exception) {
                            $this->stdout('error: ' . $e->exception->getMessage() . PHP_EOL, Console::FG_RED);
                            $fail = true;
                        } elseif ($e->element->hasErrors()) {
                            $this->stdout('failed: ' . implode(', ', $e->element->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
                            $fail = true;
                        } else {
                            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                        }
                    }
                };

                $elementsService->on(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
                $elementsService->on(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);
                $elementsService->resaveElements($query, true, updateSearchIndex: false);
                $elementsService->off(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $beforeCallback);
                $elementsService->off(Elements::EVENT_AFTER_RESAVE_ELEMENT, $afterCallback);
            });
        }

        return ExitCode::OK;
    }
}
