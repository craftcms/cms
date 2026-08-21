<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * Surface treatments for `<craft-pane>`. Mirrors `PaneAppearance` in
 * `@craftcms/ui` (src/components/pane/pane.ts).
 *
 * Deliberately separate from the shared {@see Appearance} enum: a pane’s
 * surface axis is `raised`/`sunken`, which is meaningless on buttons, callouts,
 * and the other consumers of the shared appearances.
 */
enum PaneAppearance: string
{
    case Raised = 'raised';
    case Outline = 'outline';
    case Plain = 'plain';
    case Sunken = 'sunken';
}
