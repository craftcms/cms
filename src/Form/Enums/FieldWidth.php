<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Enums;

use CraftCms\Cms\Form\Nodes\Field;

/**
 * How wide a {@see Field} should be within its
 * container, as a percentage of the available width.
 *
 * Widths are rendered as a `width-{value}` class and resolved by
 * `<craft-field-group>`, which lays its children out on a twelve-column grid
 * (see `packages/craftcms-ui/src/components/field-group/field-group.ts`). The
 * grid collapses to a single column on narrow containers, so a width is a
 * maximum rather than a guarantee.
 *
 * The field layout designer's “Number of columns” slider writes the same value
 * onto a layout element, but only offers the quarter steps.
 */
enum FieldWidth: int
{
    case Quarter = 25;
    case Third = 33;
    case Half = 50;
    case TwoThirds = 66;
    case ThreeQuarters = 75;
    case Full = 100;
}
