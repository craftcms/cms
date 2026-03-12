<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutComponent;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

class DefineShowInForm
{
    use HandleableEvent;

    public function __construct(
        public FieldLayoutComponent $fieldLayoutComponent,
        public FieldLayout $fieldLayout,
        public ?ElementInterface $element = null,
        public bool $showInForm = true,
    ) {}
}
