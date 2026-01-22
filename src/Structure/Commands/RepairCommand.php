<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Commands;

use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Support\Facades\Structures;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\NotIsNull;
use Tpetry\QueryExpressions\Value\Value;
use yii\console\ExitCode;

abstract class RepairCommand extends Command
{
    protected function repairStructure(int $structureId, ElementQueryInterface|Collection $query): int
    {
        $structure = Structures::getStructureById($structureId);

        if (! $structure) {
            $this->components->error("Invalid structure ID: $structureId");

            return self::FAILURE;
        }

        if (! $query instanceof Collection) {
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
                ->leftJoin('structureelements', function (JoinClause $join) use ($structureId) {
                    $join->on('structureelements.elementId', '=', 'elements.id')
                        ->where('structureelements.structureId', $structureId);
                })
                // Only include unpublished and provisional drafts
                ->where(function (Builder $query) {
                    $query->whereNull('elements.draftId')
                        ->orWhereNull('elements.canonicalId')
                        ->orWhere(function (Builder $query) {
                            $query->where('drafts.provisional', true)
                                ->whereNotNull('structureelements.lft');
                        });
                })
                ->orderBy(new CaseGroup(when: [
                    new CaseRule(result: '0', condition: new NotIsNull('structureelements.lft')),
                ], else: new Value(1)))
                ->orderBy('structureelements.lft')
                ->orderBy('elements.dateCreated')
                ->get();
        }

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
                        /** @var ElementInterface $element */
                        $revLabel = 'draft'.($element->draftName ? ": $element->draftName" : '');
                    } else {
                        /** @var ElementInterface $element */
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
            $this->option('dry-run') ? ' (dry run)' : '',
        );

        return ExitCode::OK;
    }
}
