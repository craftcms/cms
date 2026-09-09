<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

use CraftCms\Cms\Cp\Html\ElementHtml;

/**
 * Where an element is being shown, for deciding which of its actions belong
 * there.
 *
 * An element's action menu isn't the same everywhere. On its own edit screen the
 * element is the subject, so deleting it and jumping to the settings behind it
 * make sense. Drawn as a chip or card — in an index, a relation field, a
 * selector modal — it's one of many, and the screen around it owns its own
 * actions: a field's Remove detaches the element rather than deleting it.
 *
 * The values match the `context` strings the rest of the CP already passes
 * around ({@see ElementHtml}, `getHtmlAttributes()`), so
 * the two can be converted with `from()`/`->value` rather than kept in step by
 * hand.
 */
enum ElementActionContext: string
{
    /**
     * The element's own edit screen.
     *
     * Craft 5 expresses this as "the controller's element is this element";
     * here it's stated directly by whoever builds the menu.
     */
    case Editor = 'editor';

    /** An element index, where the element is one row among many. */
    case Index = 'index';

    /** A relation field, where the subject is the relationship to it. */
    case Field = 'field';

    /** An element selector modal. */
    case Modal = 'modal';

    /**
     * Whether this is the element's own edit screen, where the full menu
     * belongs.
     *
     * Everywhere else draws the element as a chip or card among others, and gets
     * the filtered set: destructive actions are held back, and so is anything
     * that configures what the element belongs to rather than the element.
     */
    public function isEditor(): bool
    {
        return $this === self::Editor;
    }
}
