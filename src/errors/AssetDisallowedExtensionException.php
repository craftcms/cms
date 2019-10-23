<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/**
 * Class AssetDisallowedExtensionException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetDisallowedExtensionException extends AssetException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Disallowed asset extension';
    }
}
