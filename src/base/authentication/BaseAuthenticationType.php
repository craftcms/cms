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
    public function getFormHtml(User $user): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function verify(User $user, string $code): bool
    {
        return false;
    }
}
