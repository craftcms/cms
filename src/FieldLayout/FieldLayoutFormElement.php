<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

final class FieldLayoutFormElement
{
    public function __construct(
        public FieldLayoutElement $layoutElement,
        public bool $isConditional,
        public string|bool $html,
        public bool $static,
    ) {}
}
