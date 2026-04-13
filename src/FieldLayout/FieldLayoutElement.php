<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use DateTime;
use Override;

/**
 * FieldLayoutElement is the base class for classes representing field layout elements in terms of objects.
 */
abstract class FieldLayoutElement extends FieldLayoutComponent
{
    /**
     * @var int The width (%) of the field
     */
    public int $width = 100;

    /**
     * @var DateTime|null The date that the element was added to the field layout.
     */
    public ?DateTime $dateAdded = null;

    #[Override]
    public function fields(): array
    {
        $fields = parent::fields();

        if (! $this->hasCustomWidth()) {
            unset($fields['width']);
        }

        return $fields;
    }

    /**
     * Returns whether the element can be included multiple times.
     */
    public function isMultiInstance(): bool
    {
        return false;
    }

    /**
     * Returns whether the element can have a custom width.
     */
    public function hasCustomWidth(): bool
    {
        return false;
    }

    /**
     * Returns the selector HTML that should be displayed within field layout designers.
     */
    abstract public function selectorHtml(): string;

    /**
     * Returns the element’s form HTMl.
     *
     * Return `null` if the element should not be present within the form.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    abstract public function formHtml(?ElementInterface $element = null, bool $static = false): ?string;

    /**
     * Returns whether the layout element should always be re-rendered, even if it’s already included in the form.
     */
    public function alwaysRefresh(): bool
    {
        return false;
    }

    /**
     * Returns the element container HTML attributes.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     */
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        $attributes = [];

        if ($this->hasCustomWidth()) {
            $attributes['class'][] = "width-$this->width";
        }

        return $attributes;
    }
}
