<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Contracts;

/**
 * A control whose value, along with the props it names, is everything it draws, so the control panel’s scripts can
 * read it and show it again elsewhere — the Compare screen copying it into another version of the element, say.
 *
 * A control opts in by implementing this. A field type that renders a control that implements it is readable too.
 * Copying a value takes a readable control on the side it comes from, and a {@see WritableValueControl} on the side it
 * goes to.
 *
 * @since 6.0.0
 */
interface ReadableValueControl extends Control
{
    /**
     * Returns the props the control draws its value with, besides the value itself, which are read along with it — a
     * relation field’s element presentations, say, so its chips draw.
     *
     * @return list<string>
     */
    public function valueProps(): array;

    /**
     * Returns the value scope, which a {@see WritableValueControl} must share to be given the value, when the value
     * only makes sense among controls configured alike — relations to elements a field’s settings let it select, or
     * amounts in one currency, say.
     */
    public function getValueScope(): ?string;
}
