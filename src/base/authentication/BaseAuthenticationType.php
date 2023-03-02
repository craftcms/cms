<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\authentication;

use craft\base\Component;
use craft\elements\User;
use craft\helpers\Html;

/**
 *
 * @property-read null|array $fields
 */
abstract class BaseAuthenticationType extends Component implements BaseAuthenticationInterface
{
    /**
     * @var bool
     */
    public static bool $requiresSetup = true;

    /**
     * @inheritdoc
     */
    public function getFormHtml(User $user, string $html = '', ?array $options = []): string
    {
        return Html::tag('div', $html, [
            'id' => 'verifyContainer',
            'data' => [
                'authenticator' => static::class,
            ] + $options,
        ]);
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
    public function verify(User $user, array $data): bool
    {
        return false;
    }
}
