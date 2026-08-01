<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Element\Contracts\ElementInterface;

readonly class FieldLayoutFormElementContext
{
    public function __construct(
        public FieldLayoutElement $layoutElement,
        public ?ElementInterface $element,
        public ?string $inputName,
        public mixed $value,
        public bool $readOnly,
        public ?string $inputNamespace,
    ) {}
}
