<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\UiContext;

/**
 * @since 6.0.0
 */
readonly class FieldLayoutElementContext
{
    public function __construct(
        public ?ElementInterface $element,
        public UiContext $form,
        public ControlMode $mode = ControlMode::Editable,
    ) {}
}
