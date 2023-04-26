<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use Craft;
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
        $form = Html::tag('div', $html, [
            'class' => 'so-body',
            'id' => 'mfa-setup-form',
            'data' => [
                'mfa-type' => static::class,
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
