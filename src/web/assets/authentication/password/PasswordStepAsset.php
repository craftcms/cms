<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\authentication\password;

use Craft;
use craft\validators\UserPasswordValidator;
use craft\web\assets\login\LoginAsset;
use craft\web\View;
use yii\web\AssetBundle;

/**
 * Asset bundle for the Login auth step
 */
class PasswordStepAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        LoginAsset::class
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'PasswordStep.min.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('yii', [
                '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.',
                '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.',
            ]);

            $view->registerJs(
                'window.minPasswordLength = ' . UserPasswordValidator::MIN_PASSWORD_LENGTH . ";\n" .
                'window.maxPasswordLength = ' . UserPasswordValidator::MAX_PASSWORD_LENGTH
            );
        }
    }
}
