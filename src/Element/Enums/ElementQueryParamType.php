<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

/**
 * The value shapes an element query param accepts from external APIs.
 *
 * @since 6.0.0
 */
enum ElementQueryParamType
{
    case String;
    case StringOrStrings;
    case NullableStringOrStrings;
    case IntOrInts;
    case Boolean;
    case NullableBoolean;
    case StringList;
    case Array;
}
