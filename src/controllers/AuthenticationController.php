<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\authentication\base\Step;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\UploadFailedException;
use craft\errors\UserLockedException;
use craft\events\DefineUserContentSummaryEvent;
use craft\events\InvalidUserTokenEvent;
use craft\events\LoginFailureEvent;
use craft\events\RegisterUserActionsEvent;
use craft\events\UserEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\helpers\User as UserHelper;
use craft\i18n\Locale;
use craft\models\UserGroup;
use craft\services\Users;
use craft\web\assets\edituser\EditUserAsset;
use craft\web\Controller;
use craft\web\Request;
use craft\web\ServiceUnavailableHttpException;
use craft\web\UploadedFile;
use craft\web\View;
use DateTime;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

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

        return $this->asJson($output);
    }
}
