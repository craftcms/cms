<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\authentication\base\Step;
use craft\web\Controller;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AuthenticationController class is a controller that handles various authentication related tasks.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthenticationController extends Controller
{
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    public function actionPerformAuthentication(): Response
    {
        $this->requireAcceptsJson();

        $scenario = Craft::$app->getRequest()->getRequiredBodyParam('scenario');
        $chain = Craft::$app->getAuthentication()->getAuthenticationChain($scenario);
        $step = $chain->getNextAuthenticationStep();

        $data = [];

        if ($fields = $step->getFields()) {
            foreach ($fields as $fieldName) {
                if ($value = Craft::$app->getRequest()->getBodyParam($fieldName)) {
                    $data[$fieldName] = $value;
                }
            }
        }

        $success = $chain->performAuthenticationStep($data);

        if ($chain->getIsComplete()) {
            Craft::$app->getUser()->login($chain->getAuthenticatedUser());
            $userSession = Craft::$app->getUser();
            $returnUrl = $userSession->getReturnUrl();
            $userSession->removeReturnUrl();

            return $this->asJson([
                'success' => true,
                'returnUrl' => $returnUrl
            ]);
        }


        $output = [
            'message' => Craft::$app->getSession()->getNotice(),
            'error' => Craft::$app->getSession()->getError(),
        ];

        if ($success) {
            /** @var Step $step */
            $step = $chain->getNextAuthenticationStep();
            $output['html'] = $step->getFieldHtml();
        }

        // TODO any step handle JS should be shipped here.
        return $this->asJson($output);
    }
}
