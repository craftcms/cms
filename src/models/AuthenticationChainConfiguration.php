<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\elements\User;
use Craft;

/**
 * Authentication chain configuration model class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthenticationChainConfiguration extends Model
{
    public array $steps = [];
    public string $scenario = '';
    public ?string $recoveryScenario = null;
}
