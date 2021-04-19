<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\authentication\base\Step;
use craft\web\Controller;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The AuthenticationController class is a controller that handles various authentication related tasks.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthenticationController extends Controller
{
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /** @var
     * string The session variable name to use to store whether user wants to be remembered.
     */
    private const REMEMBER_ME = 'authChain.rememberMe';

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     */
    public function actionPerformAuthentication(): Response
    {
        $this->requireAcceptsJson();

        $scenario = Craft::$app->getRequest()->getRequiredBodyParam('scenario');
        $chain = Craft::$app->getAuthentication()->getAuthenticationChain($scenario);

        try {
            $step = $chain->getNextAuthenticationStep();
        } catch (InvalidConfigException $exception) {
            throw new BadRequestHttpException('Unable to authenticate', 0, $exception);
        }

        $session = Craft::$app->getSession();
        $success = false;

        if ($step !== null) {
            $data = [];

            if ($fields = $step->getFields()) {
                foreach ($fields as $fieldName) {
                    if ($value = Craft::$app->getRequest()->getBodyParam($fieldName)) {
                        $data[$fieldName] = $value;
                    }
                }
            }

            $success = $chain->performAuthenticationStep($data);

            if ($success && Craft::$app->getRequest()->getBodyParam('rememberMe')) {
                $session->set(self::REMEMBER_ME, true);
            }
        }

        if ($chain->getIsComplete()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            if ($session->get(self::REMEMBER_ME) && $generalConfig->rememberedUserSessionDuration !== 0) {
                $duration = $generalConfig->rememberedUserSessionDuration;
            } else {
                $duration = $generalConfig->userSessionDuration;
            }

            Craft::$app->getUser()->login($chain->getAuthenticatedUser(), $duration);
            $session->remove(self::REMEMBER_ME);

            $userSession = Craft::$app->getUser();
            $returnUrl = $userSession->getReturnUrl();
            $userSession->removeReturnUrl();

            return $this->asJson([
                'success' => true,
                'returnUrl' => $returnUrl
            ]);
        }

        $output = [
            'message' => $session->getNotice(),
            'error' => $session->getError(),
        ];

        if ($success) {
            /** @var Step $step */
            $step = $chain->getNextAuthenticationStep();
            $output['stepComplete'] = true;
            $output['html'] = $step->getFieldHtml();
            $output['footHtml'] = Craft::$app->getView()->getBodyHtml();
        }

        return $this->asJson($output);
    }
}
