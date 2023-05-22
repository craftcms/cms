<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\auth;

use Craft;
use craft\base\Component;
use craft\helpers\Html;

/**
 *
 */
abstract class BaseAuthType extends Component implements BaseAuthInterface
{
    /**
     * @var bool
     */
    public static bool $requiresSetup = true;

    /**
     * @var string
     */
    private string $_fieldsNamespace = 'auth2faFields';

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
        $alternative2faHtml = Html::button(Craft::t('app', 'Alternative 2FA methods'), [
            'id' => 'alternative-2fa',
        ]) . Html::tag('ul', '', [
             'id' => 'alternative-2fa-types',
            ]);

        return Html::tag('div', $html . $alternative2faHtml, [
            'id' => 'verifyContainer',
            'data' => [
                '2fa-type' => static::class,
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
