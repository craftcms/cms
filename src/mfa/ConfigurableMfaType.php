<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mfa;

use craft\base\mfa\BaseMfaType;
use craft\elements\User;
use craft\helpers\Html;

/**
 *
 */
abstract class ConfigurableMfaType extends BaseMfaType implements ConfigurableMfaInterface
{
    /**
     * @inheritdoc
     */
    public function getSetupFormHtml(string $html = '', bool $withInto = false, ?User $user = null): string
    {
        return Html::tag('div', $html, [
            'id' => 'mfa-setup-form',
            'data' => [
                'mfa-type' => static::class,
            ],
        ]);
    }
}
