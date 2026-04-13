<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \CraftCms\Cms\Structure\Data\Structure|null getStructureById(int $structureId, bool $withTrashed = false)
 * @method static \CraftCms\Cms\Structure\Data\Structure|null getStructureByUid(string $structureUid, bool $withTrashed = false)
 * @method static void fillGapsInElements(\CraftCms\Cms\Element\Contracts\ElementInterface[] $elements)
 * @method static void applyBranchLimitToElements(\CraftCms\Cms\Element\Contracts\ElementInterface[] $elements, int $branchLimit)
 * @method static bool saveStructure(\CraftCms\Cms\Structure\Data\Structure $structure)
 * @method static bool deleteStructureById(int $structureId)
 * @method static int getElementLevelDelta(int $structureId, \CraftCms\Cms\Element\Contracts\ElementInterface $element)
 * @method static bool prepend(int $structureId, \CraftCms\Cms\Element\Contracts\ElementInterface $element, \CraftCms\Cms\Element\Contracts\ElementInterface|int $parentElement, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool append(int $structureId, \CraftCms\Cms\Element\Contracts\ElementInterface $element, \CraftCms\Cms\Element\Contracts\ElementInterface|int $parentElement, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool prependToRoot(int $structureId, \CraftCms\Cms\Element\Contracts\ElementInterface $element, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool appendToRoot(int $structureId, \CraftCms\Cms\Element\Contracts\ElementInterface $element, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool moveBefore(int $structureId, \CraftCms\Cms\Element\Contracts\ElementInterface $element, \CraftCms\Cms\Element\Contracts\ElementInterface|int $nextElement, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool moveAfter(int $structureId, \CraftCms\Cms\Element\Contracts\ElementInterface $element, \CraftCms\Cms\Element\Contracts\ElementInterface|int $prevElement, \CraftCms\Cms\Structure\Enums\Mode $mode = 'auto')
 * @method static bool remove(int $structureId, \CraftCms\Cms\Element\Contracts\ElementInterface $element)
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
