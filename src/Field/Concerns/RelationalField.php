<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use craft\base\ElementInterface;

/**
 * RelationalFieldTrait provides a base implementation for {@see \CraftCms\Cms\Field\Contracts\RelationalFieldInterface}.
 */
trait RelationalField
{
    /**
     * @see RelationalFieldInterface::localizeRelations()
     */
    public function localizeRelations(): bool
    {
        return true;
    }

    /**
     * @see RelationalFieldInterface::forceUpdateRelations()
     */
    public function forceUpdateRelations(ElementInterface $element): bool
    {
        return false;
    }
}
