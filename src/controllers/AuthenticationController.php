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

    /**
     * Get all available alternative MFA options for logging in.
     *
     * @return Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetAlternativeMfaOptions(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $currentMethod = Craft::$app->getRequest()->getRequiredBodyParam('currentMethod');
        $alternativeOptions = Craft::$app->getAuthentication()->getAlternativeMfaOptions($currentMethod);

        if ($this->request->getAcceptsJson()) {
            return $this->asSuccess(
                data: ['alternativeOptions' => $alternativeOptions],
            );
        }

        // todo: finish me
        return null;
    }

    /**
     * Return HTML for selected alternative MFA option
     *
     * @return Response|null
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionLoadAlternativeMfaOption(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $selectedMethod = Craft::$app->getRequest()->getRequiredBodyParam('selectedMethod');
        if (empty($selectedMethod)) {
            return null;
        }

        $data = Craft::$app->getAuthentication()->getDataForMfaLogin();
        if ($data['user'] !== null) {
            $mfaForm = (new $selectedMethod())->getFormHtml($data['user']);

            if ($this->request->getAcceptsJson()) {
                return $this->asSuccess(
                    data: ['mfaForm' => $mfaForm],
                );
            }
        }

        // todo: finish me
        return null;
    }
}
