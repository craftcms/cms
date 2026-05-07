<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * DefineShowFieldLayoutComponentInFormEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving} instead.
 */
class DefineShowFieldLayoutComponentInFormEvent extends Event
{
    /**
     * @var \CraftCms\Cms\FieldLayout\FieldLayout The field layout being rendered.
     */
    public FieldLayout $fieldLayout;

    /**
     * @var ElementInterface|null The element the form is being rendered for
     */
    public ?ElementInterface $element = null;

    /**
     * @var bool Whether the field layout component should be shown in the form
     */
    public bool $showInForm = true;
}
