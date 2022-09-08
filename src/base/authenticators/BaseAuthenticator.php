<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\authenticators;

use craft\base\Component;
use craft\helpers\Html;

abstract class BaseAuthenticator extends Component implements AuthenticatorInterface
{
    public ?string $handle = null;

    public ?string $label = null;
}