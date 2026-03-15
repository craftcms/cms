<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\UrlHelper;
use craft\models\GqlToken;
use craft\web\assets\graphiql\GraphiqlAsset;
use craft\web\Controller;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use CraftCms\Cms\Support\Str;
use InvalidArgumentException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response as YiiResponse;
use function CraftCms\Cms\t;

/**
 * The GqlController class is a controller that handles various GraphQL related tasks.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GraphqlController extends Controller
{
    /**
     * @inheritdoc
     * @throws NotFoundHttpException
     */
    public function beforeAction($action): bool
    {
        if (!Cms::config()->enableGql) {
            throw new NotFoundHttpException(t('Page not found.'));
        }

        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }

    /**
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionGraphiql(): YiiResponse
    {
        $this->requireAdmin(false);
        $this->getView()->registerAssetBundle(GraphiqlAsset::class);

        $schemaUid = $this->request->getQueryParam('schemaUid');
        $gqlService = Craft::$app->getGql();

        // Ensure the public schema is created.
        Craft::$app->getGql()->getPublicSchema();

        if ($schemaUid && $schemaUid !== '*') {
            try {
                $selectedSchema = $gqlService->getSchemaByUid($schemaUid);
            } catch (InvalidArgumentException) {
                throw new BadRequestHttpException('Invalid token UID.');
            }
            SessionAuth::authorize("graphql-schema:$schemaUid");
        } else {
            $selectedSchema = GqlHelper::createFullAccessSchema();
        }

        $schemas = [
            [
                'label' => t('Full Schema'),
                'value' => '*',
            ],
        ];

        foreach ($gqlService->getSchemas() as $schema) {
            $schemas[] = [
                'label' => $schema->name,
                'value' => $schema->uid,
            ];
        }

        return $this->rendertemplate('graphql/graphiql', [
            'url' => UrlHelper::actionUrl('graphql/api'),
            'schemas' => $schemas,
            'selectedSchema' => $selectedSchema,
        ]);
    }

    /**
     * @param int|null $tokenId
     * @param GqlToken|null $token
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @since 3.4.0
     */
    public function actionEditToken(?int $tokenId = null, ?GqlToken $token = null): YiiResponse
    {
        $this->requireAdmin(false);

        $gqlService = Craft::$app->getGql();
        $accessToken = null;

        if ($token || $tokenId) {
            if (!$token) {
                $token = $gqlService->getTokenById($tokenId);
            }

            if (!$token || $token->getIsPublic()) {
                throw new NotFoundHttpException('Token not found');
            }

            $title = trim($token->name ?? '') ?: t('Edit GraphQL Token');
        } else {
            $token = new GqlToken();
            $accessToken = $this->_generateToken();
            $title = trim($token->name ?? '') ?: t('Create a new GraphQL token');
        }

        $schemas = $gqlService->getSchemas();

        $schemaOptions = [];

        $publicSchema = $gqlService->getPublicSchema();

        foreach ($schemas as $schema) {
            if (!$publicSchema || $schema->id !== $publicSchema->id) {
                $schemaOptions[] = [
                    'label' => $schema->name,
                    'value' => $schema->id,
                ];
            }
        }

        if ($token->id && !$token->schemaId && !empty($schemaOptions)) {
            // Add a blank option to the top so it's clear no schema is currently selected
            array_unshift($schemaOptions, [
                'label' => '',
                'value' => '',
            ]);
        }

        return $this->rendertemplate('graphql/tokens/_edit', compact(
            'token',
            'title',
            'accessToken',
            'schemaOptions'
        ));
    }

    /**
     * @return YiiResponse|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws MissingComponentException
     * @throws Exception
     * @since 3.4.0
     */
    public function actionSaveToken(): ?YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);
        $this->requireElevatedSession();

        $gqlService = Craft::$app->getGql();
        $tokenId = $this->request->getBodyParam('tokenId');

        if ($tokenId) {
            $token = $gqlService->getTokenById($tokenId);

            if (!$token) {
                throw new NotFoundHttpException('Token not found');
            }
        } else {
            $token = new GqlToken();
        }

        $token->name = $this->request->getBodyParam('name') ?? $token->name;
        $token->accessToken = $this->request->getBodyParam('accessToken') ?? $token->accessToken;
        $token->enabled = (bool)$this->request->getRequiredBodyParam('enabled');
        $token->schemaId = $this->request->getBodyParam('schema');

        if (($expiryDate = $this->request->getBodyParam('expiryDate')) !== null) {
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        if (!$gqlService->saveToken($token)) {
            return $this->asFailure(
                t('Couldn’t save token.'),
                routeParams: [
                    'token' => $token,
                ]
            );
        }

        return $this->asSuccess(t('Schema saved.'));
    }

    /**
     * @return YiiResponse
     * @throws BadRequestHttpException
     * @since 3.4.0
     */
    public function actionDeleteToken(): YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);

        $schemaId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getGql()->deleteTokenById($schemaId);

        return $this->asSuccess();
    }


    /**
     * @return YiiResponse
     * @throws ForbiddenHttpException
     * @since 3.4.0
     */
    public function actionViewTokens(): YiiResponse
    {
        $this->requireAdmin(false);
        return $this->rendertemplate('graphql/tokens/_index');
    }

    /**
     * @return YiiResponse
     * @throws BadRequestHttpException
     */
    public function actionFetchToken(): YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);
        $this->requireElevatedSession();

        $tokenUid = $this->request->getRequiredBodyParam('tokenUid');

        try {
            $schema = Craft::$app->getGql()->getTokenByUid($tokenUid);
        } catch (InvalidArgumentException) {
            throw new BadRequestHttpException('Invalid schema UID.');
        }

        return $this->asJson([
            'accessToken' => $schema->accessToken,
        ]);
    }

    /**
     * @return YiiResponse
     */
    public function actionGenerateToken(): YiiResponse
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);

        return $this->asJson([
            'accessToken' => $this->_generateToken(),
        ]);
    }

    /**
     * @return string
     */
    private function _generateToken(): string
    {
        return Str::random(32, extendedChars: true);
    }
}
