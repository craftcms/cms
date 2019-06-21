<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\GlobalSet;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\MailerHelper;
use craft\helpers\UrlHelper;
use craft\mail\Mailer;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\TransportAdapterInterface;
use craft\models\GqlToken;
use craft\models\MailSettings;
use craft\web\assets\generalsettings\GeneralSettingsAsset;
use craft\web\Controller;
use craft\web\twig\TemplateLoaderException;
use DateTime;
use GraphQL\GraphQL;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

Craft::$app->requireEdition(Craft::Pro);

/**
 * The GqlController class is a controller that handles various GraphQL related tasks.
 * @TODO Docs
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
class GqlController extends Controller
{
    // Public Methods
    // =========================================================================

    public $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // disable csrf
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    /**
     * Perform a GQL query.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $gqlService = Craft::$app->getGql();
        $request = Craft::$app->getRequest();

        $token = null;
        $authorizationHeader = Craft::$app->request->headers->get('authorization');

        if (preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
            $accessToken = $matches[1];
            $token = $gqlService->getTokenByAccessToken($accessToken);
        }

        $tokenExpired = $token->expiryDate && $token->expiryDate->getTimestamp() <= DateTimeHelper::currentTimeStamp();

        if (!$token || !$token->enabled || $tokenExpired) {
            throw new ForbiddenHttpException('Invalid authorization token.');
        }

        $devMode = Craft::$app->getConfig()->getGeneral()->devMode;
        $schema = $gqlService->getSchema($token, $devMode);

        if ($request->getIsPost() && $query= $request->post('query')) {
            $input = $query;
        } else if ($request->getIsGet() && $query= $request->get('query')) {
            $input = $query;
        } else {
            $data = $request->getRawBody();
            $data = json_decode($data, true);
            $input = @$data['query'];
        }

        if ($input) {
            $result = GraphQL::executeQuery($schema, $input, null, null, null)->toArray(true);
        } else {
            throw new BadRequestHttpException('Request missing required param');
        }

        return $this->asJson($result);
    }

    public function actionViewTokens()
    {
        $this->requireAdmin();

        return $this->renderTemplate('settings/graphql/tokens/_index');
    }

    public function actionEditToken(int $tokenId = null, GqlToken $token = null)
    {
        $this->requireAdmin();

        $gqlService = Craft::$app->getGql();

        if ($token || $tokenId) {
            if (!$token) {
                $token = $gqlService->getTokenById($tokenId);
            }

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }

            $title = trim($token->name) ?: Craft::t('app', 'Edit GraphQL Token');
        } else {
            $token = new GqlToken();
            $title = trim($token->name) ?: Craft::t('app', 'Create a new GraphQL token');
        }

        return $this->renderTemplate('settings/graphql/tokens/_edit', compact(
            'token',
            'title'
        ));
    }

    public function actionSaveToken()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $gqlService = Craft::$app->getGql();
        $request = Craft::$app->getRequest();

        $tokenId = $request->getBodyParam('tokenId');

        if ($tokenId) {
            $token = $gqlService->getTokenById($tokenId);

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }
        } else {
            $token = new GqlToken();
        }

        $token->name = $request->getBodyParam('name');
        $token->enabled = $request->getBodyParam('enabled', false);
        $token->permissions = $request->getBodyParam('permissions');

        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $session = Craft::$app->getSession();

        if (!$gqlService->saveToken($token)) {
            $session->setError(Craft::t('app', 'Couldn’t save token.'));

            // Send the volume back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'token' => $token
            ]);

            return null;
        }

        $session->setNotice(Craft::t('app', 'Token saved.'));

        return $this->redirectToPostedUrl();
    }
}
