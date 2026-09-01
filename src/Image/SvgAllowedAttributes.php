<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use enshrined\svgSanitize\data\AllowedAttributes;
use enshrined\svgSanitize\data\AttributeInterface;

class SvgAllowedAttributes implements AttributeInterface
{
    /** @return list<string> */
    public static function getAttributes(): array
    {
        return array_merge(AllowedAttributes::getAttributes(), ['filterUnits']);
    }
}
