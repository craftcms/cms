<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\Arrayable;

/**
 * FieldLayoutElementInterface defines the common interface to be implemented by field layout element  classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
interface FieldLayoutElementInterface extends Arrayable
{
    /**
     * Returns the selector HTML that should be displayed within field layout designers.
     *
     * @return string
     */
    public function selectorHtml(): string;

    /**
     * Returns whether the element can have a custom width.
     *
     * @return bool
     */
    public function hasCustomWidth(): bool;

    /**
     * Returns the settings HTML for the layout element.
     *
     * @return string|null
     */
    public function settingsHtml();

    /**
     * Returns the element’s form HTMl.
     *
     * Return `null` if the element should not be present within the form.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return string|null
     */
    public function formHtml(ElementInterface $element = null, bool $static = false);
}
