<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Enums;

/**
 * What a choice's “All” checkbox contributes to the posted value.
 */
enum AllOptionMode: string
{
    /**
     * “All” posts a single token (`*` by default) and the options it governs
     * post nothing. Craft's relation fields store sources this way, so a field
     * set to “all” keeps picking up sources added later rather than freezing
     * the list as it stood when the field was configured.
     */
    case SingleValue = 'singleValue';

    /**
     * “All” is a select-all convenience with no value of its own: checking it
     * checks every option, and each posts its own value. Suits a closed set
     * that won't grow.
     */
    case EachValue = 'eachValue';
}
