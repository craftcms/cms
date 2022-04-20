<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\models\FieldLayoutForm;
use craft\models\FieldLayoutTab;
use yii\base\Event;

/**
 * CreateFieldLayoutFormEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class CreateFieldLayoutFormEvent extends Event
{
    /**
     * @var FieldLayoutForm The field layout form being created
     */
    public FieldLayoutForm $form;

    /**
     * @var ElementInterface|null The element the form is being rendered for
     */
    public ?ElementInterface $element = null;

    /**
     * @var bool Whether the form should be static (non-interactive)
     */
    public bool $static = false;

    /**
     * @var FieldLayoutTab[] The field layout tabs that will be displayed in the form.
     */
    public array $tabs;
}
