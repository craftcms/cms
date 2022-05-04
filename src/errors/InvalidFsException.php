<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/**
 * Class InvalidVolumeException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class InvalidFsException extends FsException
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Invalid filesystem';
    }
}
