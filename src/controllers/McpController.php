<?php

namespace craft\controllers;

use Craft;
use craft\helpers\StringHelper;
use craft\mcp\HttpTransportMiddleware;
use craft\mcp\ServerFactory;
use craft\web\Controller;
use craft\web\View;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use Mcp\Server;
use Mcp\Server\Transport\StreamableHttpTransport;
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
            $filters[$name] = is_string($filter) ? ['class' => $filter, 'only' => ['authenticated']] : $filter + ['only' => ['authenticated']];
        }

        return parent::behaviors() + $filters;
    }

    public function beforeAction($action): bool
    {
        $settings = Craft::$app->get('mcp')->getSettings();
        if (($action->id === 'authenticated' && !$settings->enabled) || ($action->id === 'public' && !$settings->publicEnabled)) {
            throw new NotFoundHttpException();
        }

        // CORS preflight requests and GET requests, which show setup instructions or are rejected, don't need
        // credentials, including while the system is offline.
        if ($action->id === 'authenticated' && ($this->request->getIsOptions() || $this->request->getIsGet())) {
            $this->allowAnonymous = self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;
        } elseif ($action->id === 'authenticated') {
            $oauth = Craft::$app->getOAuth();
            $session = Craft::$app->getUser();
            $session->enableSession = false;
            $session->enableAutoLogin = false;
            $session->setIdentity(null);

            try {
                $session->setIdentity($oauth->authenticate(ServerRequest::fromGlobals(), 'mcp', scopes: $scopes));
                Craft::$app->get('mcp')->tokenScopes = $scopes;
            } catch (OAuthServerException $exception) {
                $this->response->getHeaders()->set('WWW-Authenticate', 'Bearer resource_metadata="' . $oauth->resourceMetadataUrl('mcp') . '", scope="' . implode(' ', array_keys($oauth->getResource('mcp')->scopes)) . '"');
                $this->response->setStatusCode(401);
                $this->response->format = Response::FORMAT_JSON;
                $this->response->data = ['error' => 'invalid_token', 'error_description' => $exception->getMessage()];
                return false;
            }

            // Authenticated users go through the same checks as any other signed-in request, including control
            // panel access for control panel requests.
            $this->allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;
        } elseif ($action->id === 'public') {
            Craft::$app->getUser()->enableSession = false;
            Craft::$app->getUser()->enableAutoLogin = false;
            Craft::$app->getUser()->setIdentity(null);
        }

        return parent::beforeAction($action);
    }

    public function actionAuthenticated(): Response
    {
        $settings = Craft::$app->get('mcp')->getSettings();

        if ($this->isEndpointPageRequest()) {
            return $this->renderEndpointPage($settings->url($settings->authorizationRoute), false);
        }

        $servers = Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class);
        return $this->handle($this->request->getIsOptions() ? $servers->preflight() : $servers->authenticated());
    }

    public function actionPublic(): Response
    {
        $settings = Craft::$app->get('mcp')->getSettings();

        if ($this->isEndpointPageRequest()) {
            return $this->renderEndpointPage($settings->url($settings->publicRoute), true);
        }

        $servers = Craft::$app->get('mcp')->getCapabilityContainer()->get(ServerFactory::class);
        return $this->handle($this->request->getIsOptions() ? $servers->preflight() : $servers->public());
    }

    /** Whether a browser opened the endpoint, rather than an MCP client requesting an event stream or JSON. */
    private function isEndpointPageRequest(): bool
    {
        if (!$this->request->getIsGet()) {
            return false;
        }

        foreach (array_keys($this->request->getAcceptableContentTypes()) as $type) {
            if (in_array($type, ['text/html', 'text/*', '*/*'], true)) {
                return true;
            }
            if (in_array($type, ['text/event-stream', 'application/json', 'application/*'], true)) {
                return false;
            }
        }

        return false;
    }

    /** Renders setup instructions for people who open the endpoint in a browser. */
    private function renderEndpointPage(string $url, bool $isPublic): Response
    {
        $serverName = (StringHelper::slugify(Craft::$app->getSystemName()) ?: 'craft') . ($isPublic ? '' : '-admin');

        return $this->renderTemplate('mcp/_endpoint.twig', [
            'url' => $url,
            'serverName' => $serverName,
            'isPublic' => $isPublic,
        ], View::TEMPLATE_MODE_CP);
    }

    private function handle(Server $server): Response
    {
        if (!$this->request->getIsPost() && !$this->request->getIsOptions() && !$this->request->getIsDelete()) {
            throw new MethodNotAllowedHttpException('MCP accepts POST, DELETE, and OPTIONS requests.');
        }

        $request = ServerRequest::fromGlobals();

        // Validate the host Craft resolved (which respects trusted proxies’ X-Forwarded-Host) rather than the raw
        // Host header, which reverse proxies like Craft Cloud’s gateway may rewrite.
        $hostName = $this->request->getHostName();
        if ($hostName !== null) {
            $request = $request->withHeader('Host', $hostName);
        }

        $response = $server->run(new StreamableHttpTransport(
            request: $request,
            middleware: Craft::$app->get('mcp')->getCapabilityContainer()->get(HttpTransportMiddleware::class)->forRequest($request),
        ));

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
