<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use craft\base\ElementInterface;

/**
 * InlineEditableFieldInterface defines the common interface to be implemented by field classes
 * that wish to be editable via inline edit forms.
 */
interface InlineEditableFieldInterface extends FieldInterface
{
    /**
     * Returns the HTML that should be shown for this field’s inline inputs.
     *
     * @param  mixed  $value  The field’s value
     * @param  ElementInterface|null  $element  The element the field is associated with
     * @return string The HTML that should be shown for this field’s inline input
     */
    public function getInlineInputHtml(mixed $value, ?ElementInterface $element): string;
}
