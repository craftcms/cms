<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\UiContext;

/**
 * @since 6.0.0
 */
readonly class FieldContext
{
    /**
     * @param  string|list<string>  $path
     * @param  mixed  $value  The normalized field value
     * @param  ElementInterface|null  $element  The element being edited
     * @param  UiContext  $form  The containing UI context
     * @param  ControlMode  $mode  The field's resolved mode
     */
    public function __construct(
        public string|array $path,
        public mixed $value = null,
        public ?ElementInterface $element = null,
        public UiContext $form = new UiContext,
        public ControlMode $mode = ControlMode::Editable,
        public bool $inline = false,
    ) {}
}
