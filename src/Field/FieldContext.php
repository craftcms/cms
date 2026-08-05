<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;

readonly class FieldContext
{
    /**
     * @param  string|list<string>  $path
     * @param  mixed  $value  The normalized field value
     * @param  ElementInterface|null  $element  The element being edited
     */
    public function __construct(
        public string|array $path,
        public mixed $value = null,
        public ?ElementInterface $element = null,
    ) {}
}
