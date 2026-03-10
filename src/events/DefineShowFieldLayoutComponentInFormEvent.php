<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\Event;
use craft\models\FieldLayout;

/**
 * DefineShowFieldLayoutComponentInFormEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class DefineShowFieldLayoutComponentInFormEvent extends Event
{
    /**
     * @var FieldLayout The field layout being rendered.
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
