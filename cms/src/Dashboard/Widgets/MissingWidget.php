<?php

namespace CraftCms\Cms\Dashboard\Widgets;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

/** @since 6.0.0 */
final class MissingWidget extends Widget implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getBodyHtml(): ?string
    {
        return null;
    }
}
