<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use Craft;
use craft\base\auth\BaseAuthType;
use craft\elements\User;
use craft\helpers\Html;

/**
 *
 */
abstract class ConfigurableAuthType extends BaseAuthType implements ConfigurableAuthInterface
{
    /**
     * @inheritdoc
     */
    public function getSetupFormHtml(string $html = '', bool $withInto = false, ?User $user = null): string
    {
        $form = Html::tag('div', $html, [
            'class' => 'so-body',
            'id' => 'setup-form-2fa',
            'data' => [
                '2fa-type' => static::class,
            ],
        ]);

        if ($withInto) {
            $view = Craft::$app->getView();
            $footer = $view->renderTemplate('_components/auth/slideout-footer.twig');

            $form .= $footer;
        }

        return $form;
    }
}
