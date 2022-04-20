<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/**
 * Class FsObjectNotFoundException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FsObjectNotFoundException extends FsException
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Filesystem object not found';
    }
}
