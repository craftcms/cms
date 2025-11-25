<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use Craft;
use craft\elements\Category;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Structure\Commands\RepairCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use yii\db\Expression;

final class RepairCategoryGroupStructureCommand extends RepairCommand implements PromptsForMissingInput
{
    use CraftCommand;

    protected $signature = 'craft:utils:repair:category-group-structure {handle} {--dry-run}';

    protected $description = 'Repairs structure data for a category group.';

    protected $aliases = ['utils/repair/category-group-structure', 'repair:category-group-structure', 'repair/category-group-structure'];

    public function handle(): int
    {
        $group = Craft::$app->getCategories()->getGroupByHandle($handle = $this->argument('handle'));

        if (!$group) {
            $this->components->error("Invalid category group handle: $handle");

            return self::FAILURE;
        }

        $elements = Category::find()
            ->group($group)
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
                ['structureelements.structureId' => $group->structureId],
            ])
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
            ->collect();

        return $this->repairStructure($group->structureId, $elements);
    }
}
