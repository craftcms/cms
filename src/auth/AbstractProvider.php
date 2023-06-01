<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use craft\base\SavableComponent;

abstract class AbstractProvider extends SavableComponent implements ProviderInterface
{
    use AuthProviderTrait;
}
