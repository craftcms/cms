<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Form\Enums\ControlMode;

readonly class FormContext
{
    public ControlMode $mode;

    /**
     * @param  string|list<string>  $namespace
     * @param  array<string, mixed>  $values
     * @param  array<string, string|list<string>>  $errors
     * @param  list<string>  $globalErrors
     */
    public function __construct(
        public string|array $namespace = [],
        public array $values = [],
        public array $errors = [],
        public array $globalErrors = [],
        ControlMode|string $mode = ControlMode::Editable,
        public bool $refreshable = false,
    ) {
        $this->mode = is_string($mode) ? ControlMode::from($mode) : $mode;
    }
}
