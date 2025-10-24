<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Shared\Events\DefineHtmlEvent;

final class DefineFieldHtml extends DefineHtmlEvent
{
    public function __construct(
        public FieldInterface $field,
        public mixed $value,
        public bool $inline,
        public ?ElementInterface $element = null,
        string $html = '',
        bool $static = false
    ) {
        parent::__construct($html, $static);
    }
}
