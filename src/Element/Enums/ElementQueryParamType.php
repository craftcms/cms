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
    case StringList;
    case Int;
    /** One or more numbers, which may include operators such as `'not 1'` or `'>= 3'`. */
    case IntOrInts;
    case Boolean;
    case NullableBoolean;
    /** An ID, handle, or boolean flag. */
    case Scalar;
    /** One or more IDs or handles. */
    case ScalarOrScalars;
    case Array;
}
