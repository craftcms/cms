<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Contracts;

/**
 * A control that draws whatever value the control panel’s scripts set on it, rather than keeping state of its own, so
 * it can be given a value from elsewhere — the Compare screen copying one in from another version of the element, or
 * putting the old one back to undo that.
 *
 * A control opts in by implementing this. A field type that renders a control that implements it is writable too. A
 * value set on it comes with any props a {@see ReadableValueControl} read along with it.
 *
 * @since 6.0.0
 */
interface WritableValueControl extends Control
{
    /**
     * Returns the value scope, which a {@see ReadableValueControl} must share for its value to be set on this one.
     * Besides that, the control panel’s scripts decide whether the control takes a value from the value itself, such
     * as whether it fits the control’s length limit or options.
     */
    public function getValueScope(): ?string;
}
