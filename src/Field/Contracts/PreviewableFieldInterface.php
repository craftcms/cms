<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * PreviewableFieldInterface defines the common interface to be implemented by field classes
 * that wish to be previewable in element table and card views.
 */
interface PreviewableFieldInterface extends FieldInterface
{
    /**
     * Returns the HTML that should be shown for this field in table and card views.
     *
     * @param  mixed  $value  The field’s value
     * @param  ElementInterface  $element  The element the field is associated with
     * @return string The HTML that should be shown for this field in table and card views
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string;

    /**
     * Return the HTML that should be shown for the field in the card preview.
     * It can be used outside an element context, e.g. in a card view designer.
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string;
}
