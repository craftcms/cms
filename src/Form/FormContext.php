<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Form\Enums\ControlMode;

readonly class FormContext
{
    public readonly ControlMode $mode;

    /**
     * @param  string|list<string>  $namespace
     * @param  array<string, mixed>  $values
     * @param  array<string, string|list<string>>  $errors
     * @param  list<string>  $globalErrors
     */
    public function __construct(
        public readonly string|array $namespace = [],
        public readonly array $values = [],
        public readonly array $errors = [],
        public readonly array $globalErrors = [],
        ControlMode|string $mode = ControlMode::Editable,
        public readonly bool $refreshable = false,
    ) {
        $this->mode = is_string($mode) ? ControlMode::from($mode) : $mode;
    }
}
