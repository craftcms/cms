<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\authentication;

use craft\base\Component;
use craft\elements\User;

/**
 *
 * @property-read null|array $fields
 */
abstract class BaseAuthenticationType extends Component implements BaseAuthenticationInterface
{
    /**
     * @inheritdoc
     */
    abstract public function getFormHtml(User $user): string;

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return null;
    }
}
