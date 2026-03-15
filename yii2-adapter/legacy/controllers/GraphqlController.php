<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\UrlHelper;
use craft\web\assets\graphiql\GraphiqlAsset;
use craft\web\Controller;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use InvalidArgumentException;
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
}
