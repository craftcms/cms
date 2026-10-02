<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;

/**
 * @since 6.0.0
 */
readonly class FieldLayoutElementContext
{
    public function __construct(
        public ?ElementInterface $element,
        public FormContext $form,
        public ControlMode $mode = ControlMode::Editable,
    ) {}
}
