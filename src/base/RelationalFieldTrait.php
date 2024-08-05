<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * RelationalFieldTrait provides a base implementation for [[RelationalFieldInterface]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
trait RelationalFieldTrait
{
    public function localizeRelations(): bool
    {
        return true;
    }

    public function forceUpdateRelations(ElementInterface $element): bool
    {
        return false;
    }
}
