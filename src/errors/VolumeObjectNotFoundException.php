<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/**
 * Class VolumeFileNotFoundException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class VolumeObjectNotFoundException extends VolumeException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Volume object not found';
    }
}
