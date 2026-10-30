<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

/**
 * The value shapes an element property accepts from external APIs.
 *
 * @since 6.0.0
 */
enum ElementPropertyType
{
    case String;
    case Int;
    case Id;
    /** A list of IDs. */
    case IdList;
    case Boolean;
    case DateTime;
    /** A float, which may be passed as a numeric string to keep its precision. */
    case Float;
}
