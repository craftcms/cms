<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\authenticators;

use craft\base\Component;

class AuthenticationResult extends Component
{
    public function addAuthError($authError)
    {
        $this->addError('authError', $authError);
    }

}