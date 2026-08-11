<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormContext;

readonly class FieldContext
{
    /**
     * @param  string|list<string>  $path
     * @param  mixed  $value  The normalized field value
     * @param  ElementInterface|null  $element  The element being edited
     * @param  FormContext  $form  The containing Form context
     * @param  ControlMode  $mode  The field's resolved mode
     */
    public function __construct(
        public string|array $path,
        public mixed $value = null,
        public ?ElementInterface $element = null,
        public FormContext $form = new FormContext,
        public ControlMode $mode = ControlMode::Editable,
    ) {}
}
