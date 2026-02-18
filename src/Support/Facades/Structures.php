<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use craft\base\ElementInterface;
use CraftCms\Cms\Structure\Data\Structure;
use CraftCms\Cms\Structure\Enums\Mode;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Structure|null getStructureById(int $structureId, bool $withTrashed = false)
 * @method static Structure|null getStructureByUid(string $structureUid, bool $withTrashed = false)
 * @method static void fillGapsInElements(ElementInterface[] $elements)
 * @method static void applyBranchLimitToElements(ElementInterface[] $elements, int $branchLimit)
 * @method static bool saveStructure(Structure $structure)
 * @method static bool deleteStructureById(int $structureId)
 * @method static int getElementLevelDelta(int $structureId, ElementInterface $element)
 * @method static bool prepend(int $structureId, ElementInterface $element, ElementInterface|int $parentElement, Mode $mode = 'auto')
 * @method static bool append(int $structureId, ElementInterface $element, ElementInterface|int $parentElement, Mode $mode = 'auto')
 * @method static bool prependToRoot(int $structureId, ElementInterface $element, Mode $mode = 'auto')
 * @method static bool appendToRoot(int $structureId, ElementInterface $element, Mode $mode = 'auto')
 * @method static bool moveBefore(int $structureId, ElementInterface $element, ElementInterface|int $nextElement, Mode $mode = 'auto')
 * @method static bool moveAfter(int $structureId, ElementInterface $element, ElementInterface|int $prevElement, Mode $mode = 'auto')
 * @method static bool remove(int $structureId, ElementInterface $element)
 *
 * @see \CraftCms\Cms\Structure\Structures
 */
final class Structures extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Structure\Structures::class;
    }
}
