<?php

namespace craft\controllers;

use Craft;
use craft\mcp\HttpTransportMiddleware;
use craft\mcp\ServerFactory;
use craft\web\Controller;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use Mcp\Server\Stateless\StatelessProtocol;
use Mcp\Server\Transport\StatelessHttpTransport;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class McpController extends Controller
{
    protected array|bool|int $allowAnonymous = true;
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        $filters = [];

        foreach (Craft::$app->getConfig()->getMcp()->filters as $name => $filter) {
            $filters[$name] = is_string($filter) ? ['class' => $filter, 'only' => ['admin']] : $filter + ['only' => ['admin']];
        }

        return parent::behaviors() + $filters;
    }

    public function beforeAction($action): bool
    {
        if ($action->id === 'admin' && !$this->request->getIsOptions()) {
            $oauth = Craft::$app->getOAuth();
            $session = Craft::$app->getUser();
            $session->enableSession = false;
            $session->enableAutoLogin = false;
            $session->setIdentity(null);

            try {
                $session->setIdentity($oauth->authenticate(ServerRequest::fromGlobals(), 'mcp'));
            } catch (OAuthServerException $exception) {
                $this->response->getHeaders()->set('WWW-Authenticate', 'Bearer resource_metadata="' . $oauth->resourceMetadataUrl('mcp') . '", scope="' . implode(' ', array_keys($oauth->getResource('mcp')->scopes)) . '"');
                $this->response->setStatusCode(401);
                $this->response->format = Response::FORMAT_JSON;
                $this->response->data = ['error' => 'invalid_token', 'error_description' => $exception->getMessage()];
                return false;
            }
        } elseif ($action->id === 'public') {
            Craft::$app->getUser()->enableSession = false;
            Craft::$app->getUser()->enableAutoLogin = false;
            Craft::$app->getUser()->setIdentity(null);
        }

        return parent::beforeAction($action);
    }

    public function actionAdmin(): Response
    {
        $servers = Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class);
        return $this->handle($this->request->getIsOptions() ? $servers->preflight() : $servers->admin());
    }

    public function actionPublic(): Response
    {
        if (!Craft::$app->get('mcp')->getSettings()->publicEnabled) {
            throw new NotFoundHttpException();
        }

        $servers = Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class);
        return $this->handle($this->request->getIsOptions() ? $servers->preflight() : $servers->public());
    }

    private function handle(StatelessProtocol $protocol): Response
    {
        if (!$this->request->getIsPost() && !$this->request->getIsOptions()) {
            throw new MethodNotAllowedHttpException('MCP accepts POST and OPTIONS requests.');
        }

        $request = ServerRequest::fromGlobals();
        $response = (new StatelessHttpTransport(
            protocol: $protocol,
            middleware: Craft::$app->get('mcp')->getCapabilityContainer()->get(HttpTransportMiddleware::class)->forRequest($request),
        ))->handle($request);

        $this->response->format = Response::FORMAT_RAW;
        $this->response->setStatusCode($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            $this->response->getHeaders()->remove($name);

            foreach ($values as $value) {
                $this->response->getHeaders()->add($name, $value);
            }
        }

        $this->response->data = (string)$response->getBody();
        return $this->response;
    }
}
