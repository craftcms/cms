<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\models\FieldLayout;

/**
 * FieldLayoutElement is the base class for classes representing field layout elements in terms of objects.
 *
 * @property FieldLayout $layout The layout this element belongs to
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class FieldLayoutElement extends FieldLayoutComponent
{
    /**
     * @var int The width (%) of the field
     */
    public int $width = 100;

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();

        if (!$this->hasCustomWidth()) {
            unset($fields['width']);
        }

        return $fields;
    }

    /**
     * Returns whether the element can have a custom width.
     *
     * @return bool
     */
    public function hasCustomWidth(): bool
    {
        return false;
    }

    /**
     * Returns the selector HTML that should be displayed within field layout designers.
     *
     * @return string
     */
    abstract public function selectorHtml(): string;

    /**
     * Returns the element’s form HTMl.
     *
     * Return `null` if the element should not be present within the form.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    abstract public function formHtml(?ElementInterface $element = null, bool $static = false): ?string;

    /**
     * Returns the element container HTML attributes.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return array
     */
    protected function containerAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        $attributes = [];
        if ($this->hasCustomWidth()) {
            $attributes['class'][] = 'width-' . ($this->width ?? 100);
        }
        return $attributes;
    }
}
