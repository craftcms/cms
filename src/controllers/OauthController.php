<?php

namespace craft\controllers;

use Craft;
use craft\db\Table;
use craft\filters\IpRateLimitIdentity;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\oauth\Client;
use craft\oauth\Repositories;
use craft\oauth\User as OAuthUser;
use craft\web\Controller;
use craft\web\View;
use DateTime;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use yii\filters\RateLimiter;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class OauthController extends Controller
{
    private const MAX_CLIENT_NAME_LENGTH = 64;

    // Clients must be able to discover, register, and refresh tokens while the system is offline. Resources decide
    // whether the token's user may access them then.
    protected array|bool|int $allowAnonymous = [
        'metadata' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'resource' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'register' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'token' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'authorize' => self::ALLOW_ANONYMOUS_LIVE,
        'approve' => self::ALLOW_ANONYMOUS_LIVE,
        'deny' => self::ALLOW_ANONYMOUS_LIVE,
    ];

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
                    'keyPrefix' => 'oauth',
                    'ip' => $this->request->getUserIP() ?? 'unknown',
                ]),
            ],
            // Registration is anonymous and stores a row, and legitimate clients only register once per install
            'registrationRateLimiter' => [
                'class' => RateLimiter::class,
                'only' => ['register'],
                'enableRateLimitHeaders' => false,
                'user' => function() {
                    $config = Craft::$app->getConfig()->getOAuth();
                    if ($config->registrationRateLimit === false) {
                        return null;
                    }
                    return new IpRateLimitIdentity([
                        'limit' => $config->registrationRateLimit,
                        'window' => $config->registrationRateLimitDuration,
                        'keyPrefix' => 'oauth-register',
                        'ip' => $this->request->getUserIP() ?? 'unknown',
                    ]);
                },
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

    public function actionMetadata(string $resource): Response
    {
        return $this->asJson(Craft::$app->getOAuth()->metadata($resource));
    }

    public function actionResource(string $resource): Response
    {
        return $this->asJson(Craft::$app->getOAuth()->protectedResourceMetadata($resource));
    }

    public function actionRegister(string $resource): Response
    {
        $this->requirePostRequest();
        $definition = Craft::$app->getOAuth()->getResource($resource);
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

        $name = $data['client_name'] ?? $data['name'] ?? (parse_url($uris[0], PHP_URL_HOST) ?: 'OAuth Client');
        if (!is_string($name)) {
            return $this->error('invalid_client_metadata', 'The client name must be a string.');
        }
        $name = $this->normalizeClientName($name);
        // Names made up only of formatting characters, marks, or separators (such as variation selectors or tag
        // characters) would render empty
        if (!preg_match('/[^\p{C}\p{M}\p{Z}]/u', $name) || mb_strlen($name) > self::MAX_CLIENT_NAME_LENGTH) {
            return $this->error('invalid_client_metadata', sprintf('The client name must be between 1 and %s characters.', self::MAX_CLIENT_NAME_LENGTH));
        }
        if ($this->isReservedClientName($name)) {
            return $this->error('invalid_client_metadata', 'The client name is reserved.');
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
        if (array_key_exists('scope', $data) && (!is_string($data['scope']) || !$definition->allowsScopes(explode(' ', trim($data['scope']))))) {
            return $this->error('invalid_client_metadata', 'The requested scopes are not supported by this resource.');
        }

        $id = StringHelper::UUID();
        $now = Db::prepareDateForDb(new DateTime());
        Craft::$app->getDb()->createCommand()->insert(Table::OAUTH_CLIENTS, [
            'id' => $id,
            'name' => $name,
            'redirect_uris' => json_encode($uris, JSON_THROW_ON_ERROR),
            'grant_types' => json_encode(['authorization_code', 'refresh_token'], JSON_THROW_ON_ERROR),
            'scopes' => json_encode(array_keys($definition->scopes), JSON_THROW_ON_ERROR),
            'revoked' => false,
            'created_at' => $now,
            // Set once the client is authorized, so garbage collection can find clients that never were
            'updated_at' => null,
        ])->execute();

        $this->response->setStatusCode(201);
        return $this->asJson([
            'client_id' => $id,
            'client_name' => $name,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'redirect_uris' => $uris,
            'scope' => implode(' ', array_keys($definition->scopes)),
            'token_endpoint_auth_method' => 'none',
        ]);
    }

    public function actionAuthorize(string $resource): Response
    {
        if (!$this->request->getIsGet()) {
            throw new MethodNotAllowedHttpException('Get request required.');
        }
        $oauth = Craft::$app->getOAuth();
        $definition = $oauth->getResource($resource);
        try {
            $request = $this->psrRequest();
            if (($request->getQueryParams()['code_challenge_method'] ?? null) !== 'S256') {
                throw OAuthServerException::invalidRequest('code_challenge_method', 'Only S256 PKCE is supported.');
            }
            $authorization = $oauth->server($resource)->validateAuthorizationRequest($request);
            if (!$authorization->getScopes()) {
                throw OAuthServerException::invalidScope('(empty)');
            }
            // The token endpoint enforces the client’s scopes too, but catch them before asking for consent.
            $client = $authorization->getClient();
            foreach ($authorization->getScopes() as $scope) {
                if ($client instanceof Client && !$client->hasScope($scope->getIdentifier())) {
                    throw OAuthServerException::invalidScope($scope->getIdentifier(), $authorization->getRedirectUri());
                }
            }
            $prompts = preg_split('/\s+/', trim((string)$this->request->getQueryParam('prompt', '')), flags: PREG_SPLIT_NO_EMPTY);
            $session = Craft::$app->getSession();
            $userSession = Craft::$app->getUser();
            if (!in_array('accessCp', $definition->permissions, true)) {
                $general = Craft::$app->getConfig()->getGeneral();
                $loginPath = $general->getLoginPath() ?: $general->actionTrigger . '/users/login';
                $userSession->loginUrl = UrlHelper::siteUrl($loginPath);
            }
            $promptedLoginKey = 'oauthPromptedLogin:' . $resource;
            if ($userSession->getIsGuest()) {
                if (in_array('none', $prompts, true)) {
                    return $this->promptError($authorization, 'login_required');
                }
                $session->set($promptedLoginKey, true);
                $this->requireLogin();
            }
            if (in_array('login', $prompts, true) && !in_array('none', $prompts, true) && !$session->get($promptedLoginKey, false)) {
                $userSession->logout(false);
                $session->set($promptedLoginKey, true);
                $this->requireLogin();
            }
            $session->remove($promptedLoginKey);
            $user = $oauth->authorizedUser((string)$userSession->getId(), $resource);
            $entity = new OAuthUser();
            $entity->setIdentifier((string)$user->id);
            $authorization->setUser($entity);

            if (!in_array('consent', $prompts, true) && $oauth->reuseConsent($authorization, $resource)) {
                $authorization->setAuthorizationApproved(true);
                return $this->psrResponse($oauth->server($resource)->completeAuthorizationRequest($authorization, new PsrResponse()));
            }
            if (in_array('none', $prompts, true)) {
                return $this->promptError($authorization, 'consent_required');
            }

            $authToken = Craft::$app->getSecurity()->generateRandomString(40);
            $pending = $session->get('oauthPending', []);
            $pending = array_filter($pending, fn(array $item) => $item['expires'] > time());
            if (count($pending) >= 10) {
                array_shift($pending);
            }
            $pending[$authToken] = ['request' => serialize($authorization), 'expires' => time() + 600, 'userId' => $user->id, 'resourceId' => $definition->id];
            $session->set('oauthPending', $pending);

            $requested = array_map(fn(ScopeEntityInterface $scope) => $scope->getIdentifier(), $authorization->getScopes());
            $choices = $definition->levelChoices($requested, $user);
            if ($choices) {
                $requested = array_diff($requested, $definition->scopeLevels);
            }

            $client = $authorization->getClient();
            $redirectUri = $authorization->getRedirectUri() ?? ((array)$client->getRedirectUri())[0] ?? null;

            // Keep the consent page out of frames, so it can't be used for clickjacking
            $this->response->getHeaders()
                ->set('X-Frame-Options', 'DENY')
                ->add('Content-Security-Policy', "frame-ancestors 'none'");

            return $this->renderTemplate('oauth/authorize', [
                'clientName' => $client->getName(),
                'redirectHost' => $redirectUri ? $this->redirectHost($redirectUri) : null,
                'authToken' => $authToken,
                'consentDescription' => $definition->consentDescription,
                'accessOptions' => array_map(fn(string $level) => [
                    'value' => $level,
                    'label' => $definition->scopeLabels[$level] ?? $level,
                    'description' => $definition->scopes[$level],
                ], $choices),
                'scopes' => array_map(fn(string $scope) => $definition->scopes[$scope], array_values($requested)),
                'approveAction' => $oauth->url('approve', $resource),
                'denyAction' => $oauth->url('deny', $resource),
            ], View::TEMPLATE_MODE_CP);
        } catch (OAuthServerException $e) {
            return $this->psrResponse($e->generateHttpResponse(new PsrResponse()));
        }
    }

    public function actionApprove(string $resource): Response
    {
        return $this->completeAuthorization(true, $resource);
    }

    public function actionDeny(string $resource): Response
    {
        return $this->completeAuthorization(false, $resource);
    }

    public function actionToken(string $resource): Response
    {
        $this->requirePostRequest();
        try {
            // Serialize issuance so an authorization code or rotating refresh token cannot be redeemed twice concurrently.
            $mutex = Craft::$app->getMutex();
            $lock = 'oauth-token-' . $resource;
            if (!$mutex->acquire($lock, 10)) {
                return $this->error('temporarily_unavailable', 'Token issuance is busy. Try again.', 503);
            }
            try {
                $response = Craft::$app->getDb()->transaction(fn() => Craft::$app->getOAuth()->server($resource)->respondToAccessTokenRequest($this->psrRequest(), new PsrResponse()));
            } finally {
                $mutex->release($lock);
            }
            return $this->psrResponse($response);
        } catch (OAuthServerException $e) {
            return $this->psrResponse($e->generateHttpResponse(new PsrResponse()));
        }
    }

    private function completeAuthorization(bool $approved, string $resource): Response
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $session = Craft::$app->getSession();
        $token = $this->request->getRequiredBodyParam('authToken');
        $pending = $session->get('oauthPending', []);
        $item = is_string($token) ? ($pending[$token] ?? null) : null;
        if (!$item || $item['expires'] <= time() || $item['userId'] !== Craft::$app->getUser()->getId() || ($item['resourceId'] ?? null) !== $resource) {
            throw new BadRequestHttpException('The authorization request has expired or is invalid.');
        }
        unset($pending[$token]);
        $session->set('oauthPending', $pending);

        try {
            $oauth = Craft::$app->getOAuth();
            $user = $oauth->authorizedUser((string)$item['userId'], $resource);
            $authorization = unserialize($item['request']);
            if (!$authorization instanceof AuthorizationRequestInterface || !(new Repositories($oauth->getResource($resource)))->getClientEntity($authorization->getClient()->getIdentifier())) {
                throw new BadRequestHttpException('The authorization request is invalid.');
            }
            if ($approved) {
                $level = $this->request->getBodyParam('access');
                $oauth->approveScopes($authorization, $resource, $user, is_string($level) ? $level : null);
            }
            $authorization->setAuthorizationApproved($approved);
            return $this->psrResponse($oauth->server($resource)->completeAuthorizationRequest($authorization, new PsrResponse()));
        } catch (OAuthServerException $e) {
            return $this->psrResponse($e->generateHttpResponse(new PsrResponse()));
        }
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

    /**
     * Returns the part of a redirect URI that identifies where the authorization code will be sent: the host for web
     * URIs, or the scheme (plus host, if any) for custom app schemes.
     */
    private function redirectHost(string $uri): string
    {
        $scheme = strtolower((string)parse_url($uri, PHP_URL_SCHEME));
        $host = (string)parse_url($uri, PHP_URL_HOST);
        if (in_array($scheme, ['http', 'https'], true)) {
            return $host;
        }
        return $host !== '' ? "$scheme://$host" : "$scheme:";
    }

    /**
     * Removes invisible, private-use, and unassigned characters from a client name, and collapses its whitespace, so
     * it can’t hide or spoof text on the consent screen.
     */
    private function normalizeClientName(string $name): string
    {
        $name = preg_replace(StringHelper::invisibleCharsRegex(), '', $name) ?? '';
        $name = StringHelper::stripPrivateAndUnassignedChars($name);
        return StringHelper::collapseWhitespace($name);
    }

    /**
     * Returns whether a client name could be mistaken for Craft or this site.
     *
     * Anyone can register a client without authenticating, and the consent screen shows its name on this site’s own
     * domain, under its own branding. A client named after Craft or the site would look like a first-party app, so a
     * phishing link to the real authorize URL could trick users into granting it access. Look-alikes, such as “Craft”
     * spelled with a Cyrillic “С” (U+0421), are blocked too. Names that only contain a reserved name are allowed; the
     * redirect host shown on the consent screen covers those.
     */
    private function isReservedClientName(string $name): bool
    {
        $brandNames = [
            Craft::$app->getSystemName(),
            ...array_map(fn(Site $site) => $site->getName(), Craft::$app->getSites()->getAllSites(true)),
        ];

        // The consent screen translates the system name, and could be shown in any app or site language, regardless
        // of the language of the registration request
        $i18n = Craft::$app->getI18n();
        $languages = array_unique([...$i18n->getAppLocaleIds(), ...$i18n->getSiteLocaleIds()]);
        $reserved = ['Craft', 'Craft CMS'];
        foreach ($brandNames as $brandName) {
            $reserved[] = $brandName;
            foreach ($languages as $language) {
                $reserved[] = Craft::t('site', $brandName, language: $language);
            }
        }
        $reserved = array_unique($reserved);

        foreach ($reserved as $value) {
            if ($value !== '' && StringHelper::areConfusable($name, $value)) {
                return true;
            }
        }

        return false;
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
        $config = Craft::$app->getConfig()->getOAuth();
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
