<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\ControlMode;
use DateTimeInterface;
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
     * @var DateTimeInterface|null The date that the element was added to the field layout.
     */
    public ?DateTimeInterface $dateAdded = null;

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

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Returns the selector HTML that should be displayed within field layout designers.
     */
    abstract public function selectorHtml(): string;

    abstract public function formNode(FieldLayoutElementContext $context): ?Node;

    public function formMode(?ElementInterface $element): ControlMode
    {
        return ControlMode::Editable;
    }

    /**
     * Returns the element container HTML attributes.
     *
     * @param  ElementInterface|null  $element  The element the form is being rendered for
     * @param  bool  $static  Whether the form should be static (non-interactive)
     * @return array{class?: list<string>}
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
