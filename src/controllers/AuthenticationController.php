<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AuthenticationController class is a controller that handles various MFA-related actions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class AuthenticationController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = true;

    public function actionGetAlternativeMfaOptions(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $currentMethod = Craft::$app->getRequest()->getRequiredBodyParam('currentAuthenticator');
        $alternativeOptions = Craft::$app->getAuthentication()->getAlternativeMfaOptions($currentMethod);

        if ($this->request->getAcceptsJson()) {
            return $this->asSuccess(
                data: ['alternativeOptions' => $alternativeOptions],
            );
        }

        // todo: finish me
        return null;
    }
}
