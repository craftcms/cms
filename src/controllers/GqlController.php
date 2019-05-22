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
use craft\helpers\MailerHelper;
use craft\helpers\UrlHelper;
use craft\mail\Mailer;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\TransportAdapterInterface;
use craft\models\MailSettings;
use craft\web\assets\generalsettings\GeneralSettingsAsset;
use craft\web\Controller;
use craft\web\twig\TemplateLoaderException;
use DateTime;
use GraphQL\GraphQL;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The GqlController class is a controller that handles various GraphQL related tasks.
 * @TODO Docs
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class GqlController extends Controller
{
    // Public Methods
    // =========================================================================

    public $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All system setting actions require an admin
//        $this->requireAdmin();
    }

    /**
     * Shows the general settings form.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $start = microtime(true);
        // todo remove timers and debug parameter
        $schema = Craft::$app->getGql()->getSchema(null, Craft::$app->getRequest()->getParam('debug', false));

        if (Craft::$app->request->isPost && $query=Craft::$app->request->post('query')) {
            $input = $query;
        }
        else if (Craft::$app->request->isGet && $query=Craft::$app->request->get('query')) {
            $input = $query;
        }
        else {
            $data = Craft::$app->request->getRawBody();
            $data = json_decode($data, true);
            $input = @$data['query'];
        }

        $result = GraphQL::executeQuery($schema, $input, null, null, null)->toArray(true);


        $end = microtime(true);

        Craft::error('[GQL] Total time: ' . ($end - $start));

        $response = \Craft::$app->getResponse();
        $response->headers->add('Content-Type', 'application/json; charset=UTF-8');

        return $this->asJson($result);
    }
}
