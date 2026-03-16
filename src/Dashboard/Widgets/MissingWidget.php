<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

class MissingWidget extends Widget implements MissingComponentInterface
{
    use MissingComponentTrait;

    #[\Override]
    public function getBodyHtml(): ?string
    {
        return null;
    }
}
