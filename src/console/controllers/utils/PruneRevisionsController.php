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
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;
use yii\console\ExitCode;

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
            $sectionsService = Craft::$app->getEntries();
            $sectionIds = str($this->section)->explode(',')->map(function(string $sectionHandle) use ($sectionsService) {
                $section = $sectionsService->getSectionByHandle($sectionHandle);

                if (!$section) {
                    $this->stderr("$sectionHandle isn’t a valid section handle.\n", Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }

                return $section->id;
            })->all();
        }

        if (!isset($this->maxRevisions)) {
            $this->maxRevisions = (int)$this->prompt('What is the max number of revisions an element can have?', [
                'default' => app(GeneralConfig::class)->maxRevisions,
                'validator' => fn($input) => filter_var($input, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) !== null && $input >= 0,
            ]);
        }

        // Get the elements with too many revisions
        $subQuery = DB::table(Table::REVISIONS, 'r')
            ->select(['canonicalId', DB::raw('COUNT(*) as count')])
            ->groupBy('canonicalId')
            ->havingRaw('COUNT(*) > ' . $this->maxRevisions)
            ->when(
                value: !empty($sectionIds),
                callback: fn(Builder $query) => $query
                    ->join(new Alias(Table::ENTRIES, 'entries'), 'entries.id', '=', 'r.canonicalId')
                    ->whereIn('entries.sectionId', $sectionIds),
                default: fn(Builder $query) => $query
                    ->leftJoin(new Alias(Table::ENTRIES, 'entries'), 'entries.id', '=', 'r.canonicalId')
                    ->where(function(Builder $query) {
                        $query->whereNull('entries.id')
                            ->orWhereNotNull('entries.sectionId');
                    }),
            );

        $this->stdout('Finding elements with too many revisions ... ');

        $elements = DB::table(
            table: $subQuery,
            as: 's'
        )->select([
            's.canonicalId as id',
            's.count',
            'type' => DB::table(Table::ELEMENTS)
                ->whereColumn('id', 's.canonicalId')
                ->select('type'),
        ])->get();

        $this->stdout('done' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        if ($elements->isEmpty()) {
            $this->stdout('Nothing to prune' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('Pruning revisions ...' . PHP_EOL);

        $elementsService = Craft::$app->getElements();

        foreach ($elements as $element) {
            if (!class_exists($element->type)) {
                continue;
            }

            /** @var class-string<ElementInterface> $elementType */
            $elementType = $element->type;
            $deleteCount = $element->count - $this->maxRevisions;

            $this->stdout('- ' . $elementType::displayName() . " {$element->id} ($deleteCount revisions) ... ");

            $extraRevisions = $elementType::find()
                ->revisionOf($element->id)
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
