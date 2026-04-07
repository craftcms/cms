<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \CraftCms\Cms\Structure\Data\Structure|null getStructureById(int $structureId, bool $withTrashed = false)
 * @method static \CraftCms\Cms\Structure\Data\Structure|null getStructureByUid(string $structureUid, bool $withTrashed = false)
 * @method static void fillGapsInElements(\craft\base\ElementInterface[] $elements)
 * @method static void applyBranchLimitToElements(\craft\base\ElementInterface[] $elements, int $branchLimit)
 * @method static bool saveStructure(\CraftCms\Cms\Structure\Data\Structure $structure)
 * @method static bool deleteStructureById(int $structureId)
 * @method static int getElementLevelDelta(int $structureId, \craft\base\ElementInterface $element)
 * @method static bool prepend(int $structureId, \craft\base\ElementInterface $element, \craft\base\ElementInterface|int $parentElement, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool append(int $structureId, \craft\base\ElementInterface $element, \craft\base\ElementInterface|int $parentElement, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool prependToRoot(int $structureId, \craft\base\ElementInterface $element, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool appendToRoot(int $structureId, \craft\base\ElementInterface $element, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool moveBefore(int $structureId, \craft\base\ElementInterface $element, \craft\base\ElementInterface|int $nextElement, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool moveAfter(int $structureId, \craft\base\ElementInterface $element, \craft\base\ElementInterface|int $prevElement, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool remove(int $structureId, \craft\base\ElementInterface $element)
 *
 * @see \CraftCms\Cms\Structure\Structures
 */
class Structures extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Structure\Structures::class;
    }
}
