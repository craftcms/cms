<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;

readonly class FieldLayoutFormDefinitionContext
{
    public function __construct(
        public ?ElementInterface $element = null,
        public bool $readOnly = false,
        public ?string $inputNamespace = null,
    ) {}
}
