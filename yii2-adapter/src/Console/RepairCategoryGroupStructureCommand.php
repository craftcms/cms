<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Console;

use craft\elements\Category;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Structure\Commands\RepairCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;

final class RepairCategoryGroupStructureCommand extends RepairCommand implements PromptsForMissingInput
{
    use CraftCommand;

    protected $signature = 'craft:utils:repair:category-group-structure {handle} {--dry-run}';

    protected $description = 'Repairs structure data for a category group.';

    protected $aliases = ['utils/repair/category-group-structure', 'repair:category-group-structure', 'repair/category-group-structure'];

    public function handle(): int
    {
        $group = \Craft::$app->getCategories()->getGroupByHandle($handle = $this->argument('handle'));

        if (!$group) {
            $this->components->error("Invalid category group handle: $handle");

            return self::FAILURE;
        }

        return $this->repairStructure($group->structureId, Category::find()->group($group));
    }
}
