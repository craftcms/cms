<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/**
 * Class AuthenticationStateException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthenticationStateException extends AuthenticationException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Authentication state exception';
    }
}
