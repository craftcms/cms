<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Commands;

use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\elements\db\ElementQuery;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Support\Facades\Structures;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use yii\console\ExitCode;
use yii\db\Expression;

abstract class RepairCommand extends Command
{
    protected function repairStructure(int $structureId, ElementQuery $query): int
    {
        $structure = Structures::getStructureById($structureId);

        if (! $structure) {
            $this->components->error("Invalid structure ID: $structureId");

            return self::FAILURE;
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

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $query->elementType;
        $displayName = $elementType::pluralLowerDisplayName();

        if (empty($elements)) {
            $this->components->error("No matching $displayName to process");

            return ExitCode::OK;
        }

        $this->components->twoColumnDetail(
            'Processing '.count($elements)." $displayName",
            $this->option('dry-run') ? ' (dry run)' : '',
        );

        /** @var ElementInterface[] $ancestors */
        $ancestors = [];
        $level = 0;

        if (! $this->option('dry-run')) {
            DB::beginTransaction();
        }

        try {
            // First delete all of the existing structure data
            if (! $this->option('dry-run')) {
                StructureElement::query()
                    ->where('structureId', $structure->id)
                    ->delete();
            }

            foreach ($elements as $element) {
                /** @var ElementInterface $element */
                if (! $element->level) {
                    $issue = 'was missing from structure';
                    $newLevel = 1;
                } elseif ($element->level < 1) {
                    $issue = "had unexpected level ($element->level)";
                    $newLevel = 1;
                } elseif ($element->level > $level + 1 && (! $structure->maxLevels || $level < $structure->maxLevels)) {
                    $issue = "had unexpected level ($element->level)";
                    $newLevel = ! empty($ancestors) ? $level + 1 : 1;
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
                        if (! $this->option('dry-run')) {
                            Structures::appendToRoot($structure->id, $element, Mode::Insert);
                        }
                    } else {
                        // Make sure that the element has at least one site in common with the parent
                        $parentElement = $ancestors[$newLevel - 2];
                        $elementSites = array_map(
                            fn (array $siteInfo) => $siteInfo['siteId'],
                            ElementHelper::supportedSitesForElement($element),
                        );
                        $parentSites = array_map(
                            fn (array $siteInfo) => $siteInfo['siteId'],
                            ElementHelper::supportedSitesForElement($parentElement),
                        );

                        if (! array_intersect($elementSites, $parentSites)) {
                            $issue = 'no supported sites in common with parent';
                            if (! $this->option('dry-run')) {
                                Structures::appendToRoot($structure->id, $element, Mode::Insert);
                            }
                        } elseif (! $this->option('dry-run')) {
                            Structures::append($structure->id, $element, $parentElement, Mode::Insert);
                        }
                    }

                    $removed = false;
                }

                $line = $element->level > 1 ? str_repeat(' ', ($element->level - 1) * 4 - 2) : '';

                $line .= $element->level > 1 ? '∟ ' : '';

                if ($removed) {
                    $line .= '<fg=yellow>*</>';
                } elseif ($issue) {
                    $line .= '<fg=red>✖</>';
                } else {
                    $line .= '<fg=green>✔</>';
                }

                $line .= " {$element->title}";

                if ($element->getIsDraft() || $element->getIsRevision()) {
                    if ($element->isProvisionalDraft) {
                        $revLabel = 'provisional draft';
                    } elseif ($element->getIsUnpublishedDraft()) {
                        $revLabel = 'unpublished draft';
                    } elseif ($element->getIsDraft()) {
                        /** @var DraftBehavior|ElementInterface $element */
                        $revLabel = 'draft'.($element->draftName ? ": $element->draftName" : '');
                    } else {
                        /** @var RevisionBehavior|ElementInterface $element */
                        $revLabel = 'revision'.($element->revisionNum ? " $element->revisionNum" : '');
                    }

                    $line .= "<fg=gray> ($revLabel)</>";
                }

                if ($removed) {
                    $line .= '<fg=yellow> - removed</>';
                } elseif ($issue) {
                    $line .= "<fg=red> - $issue</>";
                }

                $this->line($line);

                // Prepare for the next element
                $ancestors = array_slice($ancestors, 0, $element->level - 1);
                $ancestors[$element->level - 1] = $element;
                $level = $element->level;
            }

            if (! $this->option('dry-run')) {
                DB::commit();
            }
        } catch (Throwable $e) {
            if (! $this->option('dry-run')) {
                DB::rollBack();
            }

            throw $e;
        }

        $this->components->twoColumnDetail(
            "Finished processing $displayName",
            $this->option('dry-run') ? ' (dry run)' : ''
        );

        return ExitCode::OK;
    }
}
