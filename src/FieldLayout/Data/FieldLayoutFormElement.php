<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Data;

use craft\base\FieldLayoutElement;
use Spatie\LaravelData\Dto;

final class FieldLayoutFormElement extends Dto
{
    public function __construct(
        public FieldLayoutElement $layoutElement,
        public bool $isConditional,
        public string|bool $html,
        public bool $static,
    ) {}
}
