<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\mfa;

use craft\base\Component;
use craft\helpers\Html;

/**
 *
 * @property-read null|array $fields
 */
abstract class BaseMfaType extends Component implements BaseMfaInterface
{
    /**
     * @var bool
     */
    public static bool $requiresSetup = true;

    /**
     * @inheritdoc
     */
    public function getInputHtml(string $html = '', array $options = []): string
    {
        return Html::tag('div', $html, [
            'id' => 'verifyContainer',
            'data' => [
                'mfa-option' => static::class,
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
    public function verify(array $data): bool
    {
        return false;
    }
}
