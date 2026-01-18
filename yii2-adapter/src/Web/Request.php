<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter\Web;

use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request as IlluminateRequest;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Cookie;
use yii\web\HeaderCollection;

/**
 * Request uses Laravel HTTP request as an input source.
 *
 * This class allows avoiding problems when request handling requires raw body reading.
 * Also it provides some useful methods from Laravel request, which can be used in Yii.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'request' => [
 *             'class' => CraftCms\Cms\Yii\Web\Request::class,
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * @see \Illuminate\Http\Request
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 *
 * @since 1.0
 */
class Request extends \yii\web\Request
{
    /**
     * {@inheritdoc}
     */
    public $csrfParam = '_token';

    private IlluminateRequest $_illuminateRequest;

    private ?HeaderCollection $_headers = null;

    /** @phpstan-ignore property.unusedType */
    private ?string $_rawBody;

    /** @phpstan-ignore property.unusedType */
    private ?array $_bodyParams;

    public function getIlluminateRequest(): IlluminateRequest
    {
        /** @var IlluminateRequest $request */
        $request = $this->_illuminateRequest ??= app('request');

        $request->setLaravelSession(session()->driver());

        return $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): HeaderCollection
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollection();
            foreach ($this->getIlluminateRequest()->headers->all() as $name => $values) {
                /** @phpstan-ignore argument.type */
                $this->_headers->set($name, $values);
            }
        }

        return $this->_headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->getIlluminateRequest()->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function getRawBody()
    {
        return $this->_rawBody ??= $this->getIlluminateRequest()->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function setRawBody($rawBody): void
    {
        $this->_rawBody = $rawBody;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyParams(): array
    {
        return $this->_bodyParams ??= match (true) {
            $this->getIlluminateRequest()->isJson() => $this->getIlluminateRequest()->json()->all(),
            $this->getContentType() === 'application/x-www-form-urlencoded' => $this->getIlluminateRequest()->request->all(),
            default => parent::getBodyParams(),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function setBodyParams($values): void
    {
        $this->_bodyParams = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getScriptUrl(): string
    {
        try {
            return parent::getScriptUrl();
        } catch (InvalidConfigException $e) {
            // Illuminate request does not provide script URL, thus set up a mock, if Yii fails to determine it
            $this->setScriptUrl('/index.php');
        }

        return parent::getScriptUrl();
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveRequestUri(): bool|string
    {
        return $this->getIlluminateRequest()->getRequestUri();
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCookies(): array
    {
        $cookies = [];

        $this->enableCookieValidation = !empty(app(GeneralConfig::class)->securityKey);

        if ($this->enableCookieValidation && $this->cookieValidationKey !== '') {
            foreach ($this->getIlluminateRequest()->cookies as $name => $value) {
                if (!is_string($value)) {
                    continue;
                }

                $data = Yii::$app->getSecurity()->validateData($value, $this->cookieValidationKey);

                if ($data === false) {
                    continue;
                }

                $data = @unserialize($data);

                if (is_array($data) && isset($data[0], $data[1]) && $data[0] === $name) {
                    $cookies[$name] = Yii::createObject([
                        'class' => Cookie::class,
                        'name' => $name,
                        'value' => $data[1],
                        'expire' => null,
                    ]);
                }
            }

            return $cookies;
        }

        foreach ($this->getIlluminateRequest()->cookies as $name => $value) {
            $cookies[$name] = Yii::createObject([
                'class' => Cookie::class,
                'name' => $name,
                'value' => $value,
                'expire' => null,
            ]);
        }

        return $cookies;
    }

    /**
     * {@inheritdoc}
     */
    public function getCsrfToken($regenerate = false): ?string
    {
        return csrf_token();
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCsrfToken(): ?string
    {
        return $this->getIlluminateRequest()->session()->token();
    }

    /**
     * {@inheritdoc}
     */
    protected function generateCsrfToken(): string
    {
        // Ensure the response is not cached by the browser or static cache proxies.
        Craft::$app->getResponse()->setNoCacheHeaders();

        $session = $this->getIlluminateRequest()->session();
        $session->regenerateToken();

        return $session->token();
    }

    /**
     * {@inheritdoc}
     */
    public function validateCsrfToken($clientSuppliedToken = null): bool
    {
        // only validate CSRF token on non-"safe" methods https://tools.ietf.org/html/rfc2616#section-9.1.1
        if (!$this->enableCsrfValidation || in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $trueToken = $this->getCsrfToken();

        if ($clientSuppliedToken !== null) {
            return $this->validateCsrfTokenInternal($clientSuppliedToken, $trueToken);
        }

        return $this->validateCsrfTokenInternal($this->getBodyParam('_token'), $trueToken)
            || $this->validateCsrfTokenInternal($this->getCsrfTokenFromHeader(), $trueToken);
    }

    /**
     * Validates CSRF token.
     *
     * @see \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::tokensMatch()
     *
     * @param  mixed  $clientSuppliedToken  The masked client-supplied token.
     * @param  string  $trueToken  The masked true token.
     */
    private function validateCsrfTokenInternal(mixed $clientSuppliedToken, string $trueToken): bool
    {
        if (!is_string($clientSuppliedToken)) {
            return false;
        }

        return hash_equals($trueToken, $clientSuppliedToken);
    }
}
