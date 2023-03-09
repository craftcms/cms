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
     * @var string
     */
    private string $_fieldsNamespace = 'mfaFields';

    /**
     * @inheritdoc
     */
    public function getNamespacedFields(): array
    {
        $fields = $this->getFields();
        $namespacedFields = [];

        if (!empty($fields)) {
            foreach ($fields as $key => $value) {
                $namespacedFields[] = [
                    'key' => $key,
                    'label' => $value,
                    'namespacedKey' => $this->_fieldsNamespace . '[' . $key . ']',
                ];
            }
        }

        return $namespacedFields;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(string $html = '', array $options = []): string
    {
        return Html::tag('div', $html, [
            'id' => 'verifyContainer',
            'data' => [
                'mfa-type' => static::class,
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
