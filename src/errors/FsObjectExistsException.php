<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/**
 * Class FsObjectExistsException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FsObjectExistsException extends FsException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'File system object exists';
    }
}
