<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Component\Concerns\ConfigConstructor;

final class FieldLayoutFormElement
{
    use ConfigConstructor;

    public function __construct(
        public FieldLayoutElement $layoutElement,
        public bool $isConditional,
        public string|bool $html,
        public bool $static,
    ) {}
}
