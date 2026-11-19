<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use Closure;
use craft\base\ElementInterface;

/**
 * Holds a link’s eager loader and eager-loaded element. Clones of the link share it, and it serializes empty.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 * @internal
 */
class LinkEagerLoadState
{
    public ?Closure $loader = null;
    public ?ElementInterface $element = null;

    /**
     * @return array{}
     */
    public function __serialize(): array
    {
        return [];
    }
}
