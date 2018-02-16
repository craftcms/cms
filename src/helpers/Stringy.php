<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

/**
 * The entire purpose of this class is so we can get at the charsArray in Stringy, which is a protected method
 * and the creators did not want to expose as public.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Stringy extends \Stringy\Stringy
{
    /**
     * Call Stringy's `charsArray` for backwards compatibility.
     *
     * @return array
     */
    public function getAsciiCharMap(): array
    {
        return $this->charsArray();
    }
}
