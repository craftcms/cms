<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/**
 * Class ImageTransformException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransformException extends AssetException
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Image Transform Error';
    }
}
