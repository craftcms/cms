<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * Content treatments for `<craft-pane>`, layered on top of the appearance.
 * Mirrors `PaneVariant` in `@craftcms/ui` (src/components/pane/pane.ts).
 *
 * Deliberately separate from the shared {@see Variant} enum: those values are
 * semantic colors (`success`, `warning`, …), whereas a pane’s variant selects a
 * treatment for its contents — an error surface or a scrollable code well.
 */
enum PaneVariant: string
{
    case Plain = 'plain';
    case Error = 'error';
    case Code = 'code';
}
