<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use Craft;
use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\console\Controller;
use craft\elements\Category;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\ElementHelper;
use craft\models\Section;
use craft\records\StructureElement;
use craft\services\ProjectConfig;
use craft\services\Structures;
use Throwable;
use yii\console\ExitCode;
use yii\db\Expression;

/**
 * Repairs data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.24
 */
class RepairController extends Controller
{
    /**
     * @var bool Whether to only do a dry run of the repair process.
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
     * Repairs structure data for a section.
     *
     * @param string $handle The section handle.
     * @return int
     */
    public function actionSectionStructure(string $handle): int
    {
        $section = Craft::$app->getSections()->getSectionByHandle($handle);

        if (!$section) {
            $this->stderr("Invalid section handle: $handle" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($section->type !== Section::TYPE_STRUCTURE) {
            $this->stderr("$section->name is not a Structure section" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return $this->repairStructure($section->structureId, Entry::find()->section($section));
    }

    /**
     * Repairs structure data for a category group.
     *
     * @param string $handle The category group handle.
     * @return int
     */
    public function actionCategoryGroupStructure(string $handle): int
    {
        $group = Craft::$app->getCategories()->getGroupByHandle($handle);

        if (!$group) {
            $this->stderr("Invalid category group handle: $handle" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return $this->repairStructure($group->structureId, Category::find()->group($group));
    }

    /**
     * Repairs the structure for elements that match the given element query.
     *
     * @param int $structureId
     * @param ElementQuery $query
     * @return int
     */
    protected function repairStructure(int $structureId, ElementQuery $query): int
    {
        $structuresService = Craft::$app->getStructures();
        $structure = $structuresService->getStructureById($structureId);

        if (!$structure) {
            $this->stderr("Invalid structure ID: $structureId" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Get all the elements that match the query, including ones that may not be part of the structure
        $elements = $query
            ->site('*')
            ->unique()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->status(null)
            ->withStructure(false)
            ->addSelect([
                'structureelements.root',
                'structureelements.lft',
                'structureelements.rgt',
                'structureelements.level',
            ])
            ->leftJoin('{{%structureelements}} structureelements', [
                'and',
                '[[structureelements.elementId]] = [[elements.id]]',
                ['structureelements.structureId' => $structureId],
            ])
            // Only include unpublished and provisional drafts
            ->andWhere([
                'or',
                ['elements.draftId' => null],
                ['elements.canonicalId' => null],
                ['and', ['drafts.provisional' => true], ['not', ['structureelements.lft' => null]]],
            ])
            ->orderBy([
                new Expression('CASE WHEN [[structureelements.lft]] IS NOT NULL THEN 0 ELSE 1 END ASC'),
                'structureelements.lft' => SORT_ASC,
                'elements.dateCreated' => SORT_ASC,
            ])
            ->all();

        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $query->elementType;
        $displayName = $elementType::pluralLowerDisplayName();

        if (empty($elements)) {
            $this->stdout("No matching $displayName to process" . PHP_EOL);
            return ExitCode::OK;
        }

        $this->stdout('Processing ' . count($elements) . " $displayName" . ($this->dryRun ? ' (dry run)' : '') . ' ...' . PHP_EOL);

        /** @var ElementInterface[] $ancestors */
        $ancestors = [];
        $level = 0;

        if (!$this->dryRun) {
            $transaction = Craft::$app->getDb()->beginTransaction();
        }

        try {
            // First delete all of the existing structure data
            if (!$this->dryRun) {
                StructureElement::deleteAll([
                    'structureId' => $structureId,
                ]);
            }

            foreach ($elements as $element) {
                /** @var ElementInterface $element */
                if (!$element->level) {
                    $issue = 'was missing from structure';
                    $newLevel = 1;
                } elseif ($element->level < 1) {
                    $issue = "had unexpected level ($element->level)";
                    $newLevel = 1;
                } elseif ($element->level > $level + 1 && (!$structure->maxLevels || $level < $structure->maxLevels)) {
                    $issue = "had unexpected level ($element->level)";
                    $newLevel = !empty($ancestors) ? $level + 1 : 1;
                } elseif ($structure->maxLevels && $element->level > $structure->maxLevels) {
                    $issue = "exceeded the max level ($structure->maxLevels)";
                    $newLevel = isset($ancestors[$level - 2]) ? $level : 1;
                } else {
                    $issue = null;
                    $newLevel = $element->level;
                }

                // Skip provisional drafts if they exist directly after their canonical element
                if (
                    $element->isProvisionalDraft &&
                    isset($ancestors[$newLevel - 1]) &&
                    $element->getCanonicalId() == $ancestors[$newLevel - 1]->id
                ) {
                    $removed = true;
                } else {
                    if ($newLevel == 1) {
                        if (!$this->dryRun) {
                            $structuresService->appendToRoot($structureId, $element, Structures::MODE_INSERT);
                        }
                    } else {
                        // Make sure that the element has at least one site in common with the parent
                        $parentElement = $ancestors[$newLevel - 2];
                        $elementSites = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($element), 'siteId');
                        $parentSites = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($parentElement), 'siteId');

                        if (!array_intersect($elementSites, $parentSites)) {
                            $issue = 'no supported sites in common with parent';
                            if (!$this->dryRun) {
                                $structuresService->appendToRoot($structureId, $element, Structures::MODE_INSERT);
                            }
                        } elseif (!$this->dryRun) {
                            $structuresService->append($structureId, $element, $parentElement, Structures::MODE_INSERT);
                        }
                    }

                    $removed = false;
                }

                $this->stdout(' ');

                $space = $element->level > 1 ? str_repeat(' ', ($element->level - 1) * 4 - 2) : '';

                if ($removed) {
                    $this->stdout('*', Console::FG_YELLOW);
                } elseif ($issue) {
                    $this->stdout('✖', Console::FG_RED);
                } else {
                    $this->stdout('✔', Console::FG_GREEN);
                }

                $this->stdout(" $space" . ($element->level > 1 ? '∟ ' : '') . $element->title);

                if ($element->getIsDraft() || $element->getIsRevision()) {
                    if ($element->isProvisionalDraft) {
                        $revLabel = 'provisional draft';
                    } elseif ($element->getIsUnpublishedDraft()) {
                        $revLabel = 'unpublished draft';
                    } elseif ($element->getIsDraft()) {
                        /** @var DraftBehavior|ElementInterface $element */
                        $revLabel = 'draft' . ($element->draftName ? ": $element->draftName" : '');
                    } else {
                        /** @var RevisionBehavior|ElementInterface $element */
                        $revLabel = 'revision' . ($element->revisionNum ? " $element->revisionNum" : '');
                    }
                    $this->stdout(" ($revLabel)", Console::FG_GREY);
                }

                if ($removed) {
                    $this->stdout(' - removed', Console::FG_YELLOW);
                } elseif ($issue) {
                    $this->stdout(" - $issue", Console::FG_RED);
                }

                $this->stdout(PHP_EOL);

                // Prepare for the next element
                $ancestors = array_slice($ancestors, 0, $element->level - 1);
                $ancestors[$element->level - 1] = $element;
                $level = $element->level;
            }

            if (isset($transaction)) {
                $transaction->commit();
            }
        } catch (Throwable $e) {
            if (isset($transaction)) {
                $transaction->rollBack();
            }
            throw $e;
        }

        $this->stdout("Finished processing $displayName" . ($this->dryRun ? ' (dry run)' : '') . PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Repairs double-packed associative arrays in the project config.
     *
     * @since 3.4.26
     */
    public function actionProjectConfig(): int
    {
        $projectConfigService = Craft::$app->getProjectConfig();
        $config = $projectConfigService->get();

        $this->stdout('Repairing project config ...' . PHP_EOL);
        foreach ($config as $key => $value) {
            $this->_repairProjectConfigItem($projectConfigService, $key, $value);
        }
        $this->stdout('Finished repairing project config' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Repairs a single item within the project config, recursively.
     *
     * @param ProjectConfig $projectConfigService
     * @param string $path
     * @param mixed $value
     * @return mixed
     */
    private function _repairProjectConfigItem(ProjectConfig $projectConfigService, string $path, mixed $value): mixed
    {
        if (is_array($value)) {
            // Is this a packed array?
            if (isset($value[ProjectConfig::ASSOC_KEY])) {
                $double = false;
                while (
                    isset($value[ProjectConfig::ASSOC_KEY][0][0]) &&
                    $value[ProjectConfig::ASSOC_KEY][0][0] === ProjectConfig::ASSOC_KEY
                ) {
                    $value[ProjectConfig::ASSOC_KEY] = $value[ProjectConfig::ASSOC_KEY][0][1] ?? [];
                    $double = true;
                }

                if ($double) {
                    $this->stdout("- double-packed array found at $path" . PHP_EOL);
                    $projectConfigService->set($path, $value);
                }
            }

            foreach ($value as $k => $v) {
                $value[$k] = $this->_repairProjectConfigItem($projectConfigService, "$path.$k", $v);
            }
        }

        return $value;
    }
}
