<?php

namespace craft\controllers;

use Craft;
use craft\db\Query;
use craft\filters\IpRateLimitIdentity;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\mcp\oauth\OAuth;
use craft\mcp\oauth\Repositories;
use craft\mcp\oauth\User as OAuthUser;
use craft\web\Controller;
use craft\web\View;
use DateTime;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use yii\filters\RateLimiter;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class McpOAuthController extends Controller
{
    protected array|bool|int $allowAnonymous = ['metadata', 'resource', 'register', 'authorize', 'token'];

    public function behaviors(): array
    {
        return parent::behaviors() + [
            'rateLimiter' => [
                'class' => RateLimiter::class,
                'only' => ['register', 'authorize', 'token'],
                'enableRateLimitHeaders' => false,
                'user' => fn() => new IpRateLimitIdentity([
                    'limit' => 60,
                    'window' => 60,
                    'keyPrefix' => 'mcp-oauth',
                    'ip' => $this->request->getUserIP() ?? 'unknown',
                ]),
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        if (in_array($action->id, ['register', 'token'], true)) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actionMetadata(): Response
    {
        return $this->asJson((new OAuth())->metadata());
    }

    public function actionResource(): Response
    {
        return $this->asJson((new OAuth())->protectedResourceMetadata());
    }

    public function actionRegister(): Response
    {
        $this->requirePostRequest();
        $data = $this->request->getBodyParams();
        $uris = $data['redirect_uris'] ?? null;
        if (!is_array($uris) || !array_is_list($uris) || !$uris || count($uris) > 20) {
            return $this->error('invalid_redirect_uri', 'Provide a list of redirect URIs.');
        }
        foreach ($uris as $uri) {
            if (!is_string($uri) || !$this->validRedirectUri($uri)) {
                return $this->error('invalid_redirect_uri', 'A redirect URI is invalid or not permitted.');
            }
        }

        $name = $data['client_name'] ?? $data['name'] ?? (parse_url($uris[0], PHP_URL_HOST) ?: 'MCP Client');
        if (!is_string($name) || trim($name) === '' || mb_strlen($name) > 255) {
            return $this->error('invalid_client_metadata', 'The client name must be between 1 and 255 characters.');
        }
        if (isset($data['token_endpoint_auth_method']) && $data['token_endpoint_auth_method'] !== 'none') {
            return $this->error('invalid_client_metadata', 'Only public clients are supported.');
        }
        if (isset($data['grant_types']) && (
            !is_array($data['grant_types']) ||
            !array_is_list($data['grant_types']) ||
            array_filter($data['grant_types'], 'is_string') !== $data['grant_types'] ||
            !in_array('authorization_code', $data['grant_types'], true) ||
            array_diff($data['grant_types'], ['authorization_code', 'refresh_token'])
        )) {
            return $this->error('invalid_client_metadata', 'Only authorization_code and refresh_token grants are supported.');
        }
        if (isset($data['response_types']) && $data['response_types'] !== ['code']) {
            return $this->error('invalid_client_metadata', 'Only the code response type is supported.');
        }
        if (isset($data['scope']) && $data['scope'] !== OAuth::SCOPE) {
            return $this->error('invalid_client_metadata', 'Only the MCP scope is supported.');
        }

        $id = StringHelper::UUID();
        Craft::$app->getDb()->createCommand()->insert(Repositories::CLIENTS, [
            'id' => $id,
            'name' => $name,
            'redirectUris' => json_encode($uris, JSON_THROW_ON_ERROR),
            'revoked' => false,
            'dateCreated' => Db::prepareDateForDb(new DateTime()),
        ])->execute();

        $this->response->setStatusCode(201);
        return $this->asJson([
            'client_id' => $id,
            'client_name' => $name,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'redirect_uris' => $uris,
            'scope' => OAuth::SCOPE,
            'token_endpoint_auth_method' => 'none',
        ]);
    }

    public function actionAuthorize(): Response
    {
        if (!$this->request->getIsGet()) {
            throw new MethodNotAllowedHttpException('Get request required.');
        }
        $oauth = new OAuth();
        try {
            $request = $this->psrRequest();
            if (($request->getQueryParams()['code_challenge_method'] ?? null) !== 'S256') {
                throw OAuthServerException::invalidRequest('code_challenge_method', 'Only S256 PKCE is supported.');
            }
            $authorization = $oauth->server()->validateAuthorizationRequest($request);
            $prompts = preg_split('/\s+/', trim((string)$this->request->getQueryParam('prompt', '')), flags: PREG_SPLIT_NO_EMPTY);
            $session = Craft::$app->getSession();
            $userSession = Craft::$app->getUser();
            if ($userSession->getIsGuest()) {
                if (in_array('none', $prompts, true)) {
                    return $this->promptError($authorization, 'login_required');
                }
                $session->set('mcpOAuthPromptedLogin', true);
                $this->requireLogin();
            }
            if (in_array('login', $prompts, true) && !in_array('none', $prompts, true) && !$session->get('mcpOAuthPromptedLogin', false)) {
                $userSession->logout(false);
                $session->set('mcpOAuthPromptedLogin', true);
                $this->requireLogin();
            }
            $session->remove('mcpOAuthPromptedLogin');
            $user = OAuth::authorizedUser((string)$userSession->getId());
            $entity = new OAuthUser();
            $entity->setIdentifier((string)$user->id);
            $authorization->setUser($entity);

            if (!in_array('consent', $prompts, true) && $this->hasConsent($authorization)) {
                $authorization->setAuthorizationApproved(true);
                return $this->psrResponse($oauth->server()->completeAuthorizationRequest($authorization, new PsrResponse()));
            }
            if (in_array('none', $prompts, true)) {
                return $this->promptError($authorization, 'consent_required');
            }

            $authToken = Craft::$app->getSecurity()->generateRandomString(40);
            $pending = $session->get('mcpOAuthPending', []);
            $pending = array_filter($pending, fn(array $item) => $item['expires'] > time());
            if (count($pending) >= 10) {
                array_shift($pending);
            }
            $pending[$authToken] = ['request' => serialize($authorization), 'expires' => time() + 600, 'userId' => $user->id];
            $session->set('mcpOAuthPending', $pending);

            return $this->renderTemplate('mcp/authorize', [
                'clientName' => $authorization->getClient()->getName(),
                'authToken' => $authToken,
                'approveAction' => $oauth->url('approve'),
                'denyAction' => $oauth->url('deny'),
            ], View::TEMPLATE_MODE_CP);
        } catch (OAuthServerException $e) {
            return $this->psrResponse($e->generateHttpResponse(new PsrResponse()));
        }
    }

    public function actionApprove(): Response
    {
        return $this->completeAuthorization(true);
    }

    public function actionDeny(): Response
    {
        return $this->completeAuthorization(false);
    }

    public function actionToken(): Response
    {
        $this->requirePostRequest();
        try {
            // Serialize issuance so an authorization code or rotating refresh token cannot be redeemed twice concurrently.
            $mutex = Craft::$app->getMutex();
            $lock = 'mcp-oauth-token';
            if (!$mutex->acquire($lock, 10)) {
                return $this->error('temporarily_unavailable', 'Token issuance is busy. Try again.', 503);
            }
            try {
                $response = Craft::$app->getDb()->transaction(fn() => (new OAuth())->server()->respondToAccessTokenRequest($this->psrRequest(), new PsrResponse()));
            } finally {
                $mutex->release($lock);
            }
            return $this->psrResponse($response);
        } catch (OAuthServerException $e) {
            return $this->psrResponse($e->generateHttpResponse(new PsrResponse()));
        }
    }

    private function completeAuthorization(bool $approved): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('accessCp');
        $this->requirePermission('useCraftMcp');
        $session = Craft::$app->getSession();
        $token = $this->request->getRequiredBodyParam('authToken');
        $pending = $session->get('mcpOAuthPending', []);
        $item = is_string($token) ? ($pending[$token] ?? null) : null;
        if (!$item || $item['expires'] <= time() || $item['userId'] !== Craft::$app->getUser()->getId()) {
            throw new BadRequestHttpException('The authorization request has expired or is invalid.');
        }
        unset($pending[$token]);
        $session->set('mcpOAuthPending', $pending);

        try {
            OAuth::authorizedUser((string)$item['userId']);
            $authorization = unserialize($item['request']);
            if (!$authorization instanceof AuthorizationRequestInterface || !(new Repositories())->getClientEntity($authorization->getClient()->getIdentifier())) {
                throw new BadRequestHttpException('The authorization request is invalid.');
            }
            $authorization->setAuthorizationApproved($approved);
            return $this->psrResponse((new OAuth())->server()->completeAuthorizationRequest($authorization, new PsrResponse()));
        } catch (OAuthServerException $e) {
            return $this->psrResponse($e->generateHttpResponse(new PsrResponse()));
        }
    }

    private function hasConsent(AuthorizationRequestInterface $request): bool
    {
        $rows = (new Query())->select(['scopes'])->from(Repositories::TOKENS)->where([
            'clientId' => $request->getClient()->getIdentifier(),
            'userId' => $request->getUser()->getIdentifier(),
            'revoked' => false,
        ])->andWhere(['>', 'expiresAt', Db::prepareDateForDb(new DateTime())])->all();
        $granted = [];
        foreach ($rows as $row) {
            $granted = array_merge($granted, json_decode($row['scopes'], true, flags: JSON_THROW_ON_ERROR));
        }
        $requested = array_map(fn($scope) => $scope->getIdentifier(), $request->getScopes());
        return $rows && !array_diff($requested, $granted);
    }

    private function promptError(AuthorizationRequestInterface $request, string $error): Response
    {
        $uri = $request->getRedirectUri();
        if (!$uri) {
            $uris = $request->getClient()->getRedirectUri();
            $uri = is_array($uris) ? $uris[0] : $uris;
        }
        $query = http_build_query(['error' => $error, 'state' => $request->getState()]);
        return $this->redirect($uri . (str_contains($uri, '?') ? '&' : '?') . $query);
    }

    private function validRedirectUri(string $uri): bool
    {
        if (strlen($uri) > 2048 || preg_match('/[\x00-\x20\x7f]/', $uri)) {
            return false;
        }
        $parts = parse_url($uri);
        if (!$parts || empty($parts['scheme']) || empty($parts['host']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        $config = Craft::$app->getConfig()->getMcp();
        if (!in_array($parts['scheme'], ['http', 'https'], true)) {
            return in_array($parts['scheme'], $config->customSchemes, true);
        }
        if (!filter_var($uri, FILTER_VALIDATE_URL)) {
            return false;
        }
        if (in_array('*', $config->redirectDomains, true)) {
            return true;
        }
        foreach ($config->redirectDomains as $domain) {
            $allowed = parse_url($domain);
            if (!$allowed || !isset($allowed['scheme'], $allowed['host'])) {
                continue;
            }
            $loopback = in_array($parts['host'], ['localhost', '127.0.0.1', '[::1]'], true) && $parts['scheme'] === 'http';
            if ($parts['scheme'] === $allowed['scheme'] && strcasecmp($parts['host'], $allowed['host']) === 0 && ($loopback || ($parts['port'] ?? null) === ($allowed['port'] ?? null))) {
                $path = rtrim($allowed['path'] ?? '', '/') . '/';
                if (str_starts_with(($parts['path'] ?? '/') . '/', $path)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function psrRequest(): ServerRequestInterface
    {
        return ServerRequest::fromGlobals()->withQueryParams($this->request->getQueryParams())->withParsedBody($this->request->getBodyParams());
    }

    private function psrResponse(ResponseInterface $response): Response
    {
        $this->response->format = Response::FORMAT_RAW;
        $this->response->setStatusCode($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            $this->response->getHeaders()->remove($name);
            foreach ($values as $value) {
                $this->response->getHeaders()->add($name, $value);
            }
        }
        $this->response->content = (string)$response->getBody();
        return $this->response;
    }

    private function error(string $error, string $description, int $status = 400): Response
    {
        $this->response->setStatusCode($status);
        return $this->asJson(['error' => $error, 'error_description' => $description]);
    }
}
