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
use craft\helpers\StringHelper;
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
     * @var string|null The section handle(s) to prune revisions from. Can be set to multiple comma-separated sections.
     * @since 4.2.0
     */
    public ?string $section = null;

    /**
     * @var int|null The maximum number of revisions an element can have.
     */
    public ?int $maxRevisions = null;

    /**
     * @var bool Whether this is a dry run.
     * @since 3.7.9
     */
    public bool $dryRun = false;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'section';
        $options[] = 'maxRevisions';
        $options[] = 'dryRun';
        return $options;
    }

    /**
     * Prunes excess element revisions.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $sectionIds = [];
        if ($this->section) {
            $sectionsService = Craft::$app->getSections();
            $sectionHandles = StringHelper::split($this->section);
            foreach ($sectionHandles as $sectionHandle) {
                $section = $sectionsService->getSectionByHandle($sectionHandle);
                if (!$section) {
                    $this->stderr("$sectionHandle isn’t a valid section handle.\n", Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
                $sectionIds[] = $section->id;
            }
        }

        if (!isset($this->maxRevisions)) {
            $this->maxRevisions = (int)$this->prompt('What is the max number of revisions an element can have?', [
                'default' => Craft::$app->getConfig()->getGeneral()->maxRevisions,
                'validator' => function($input) {
                    return filter_var($input, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) !== null && $input >= 0;
                },
            ]);
        }

        // Get the elements with too many revisions
        $subQuery = (new Query())
            ->select(['canonicalId', 'count' => 'COUNT(*)'])
            ->from(['r' => Table::REVISIONS])
            ->groupBy(['canonicalId'])
            ->having(['>', 'COUNT(*)', $this->maxRevisions]);

        if (!empty($sectionIds)) {
            $subQuery
                ->innerJoin(['entries' => Table::ENTRIES], '[[entries.id]] = [[r.canonicalId]]')
                ->andWhere(['entries.sectionId' => $sectionIds]);
        }

        $this->stdout('Finding elements with too many revisions ... ');
        $elements = (new Query())
            ->select([
                'id' => 's.canonicalId',
                's.count',
                'type' => (new Query())
                    ->select(['type'])
                    ->from([Table::ELEMENTS])
                    ->where(new Expression('[[id]] = [[s.canonicalId]]')),
            ])
            ->from(['s' => $subQuery])
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

            $this->stdout('- ' . $elementType::displayName() . " {$element['id']} ($deleteCount revisions) ... ");

            $extraRevisions = $elementType::find()
                ->revisionOf($element['id'])
                ->site('*')
                ->unique()
                ->status(null)
                ->orderBy(['num' => SORT_DESC])
                ->offset($this->maxRevisions)
                ->all();

            if (!$this->dryRun) {
                foreach ($extraRevisions as $extraRevision) {
                    $elementsService->deleteElement($extraRevision, true);
                }
            }

            $this->stdout('done', Console::FG_GREEN);

            if (count($extraRevisions) !== $deleteCount) {
                $this->stdout(' (found ' . count($extraRevisions) . ')', Console::FG_RED);
            }

            $this->stdout(PHP_EOL);
        }

        $this->stdout(PHP_EOL . 'Finished pruning revisions' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
