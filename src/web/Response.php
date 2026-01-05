<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Session;
use craft\helpers\UrlHelper;
use Throwable;
use yii\base\Application as BaseApplication;
use yii\web\Cookie;
use yii\web\CookieCollection;
use yii\web\HttpException;

/**
 * @inheritdoc
 * @mixin CpScreenResponseBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Response extends \yii\web\Response
{
    /**
     * @since 3.4.0
     */
    public const FORMAT_CSV = 'csv';

    /**
     * @since 4.16.10
     */
    public const FORMAT_GQL = 'gql';

    /**
     * @since 5.9.0
     */
    public const FORMAT_XLSX = 'xlsx';

    /**
     * @since 5.9.0
     */
    public const FORMAT_YAML = 'yaml';

    /**
     * Default response formatter configurations.
     *
     * This could be set from `config/app.web.php` to append additional default response formatters, or modify existing ones.
     *
     * ```php
     * use craft\helpers\App;
     * use craft\helpers\ArrayHelper;
     * use craft\web\Response;
     *
     * return [
     *     'components' => [
     *         'response' => fn() => Craft::createObject(ArrayHelper::merge(
     *             App::webResponseConfig(),
     *             [
     *                 'defaultFormatters' => [
     *                     Response::FORMAT_CSV => [
     *                         'delimiter' => chr(9),
     *                     ],
     *                 ],
     *             ]
     *         )),
     *     ],
     * ];
     * ```
     *
     * @see defaultFormatters()
     * @since 4.5.0
     */
    public array $defaultFormatters = [];

    /**
     * @var bool whether the response has been prepared.
     */
    private bool $_isPrepared = false;

    /**
     * @var CookieCollection Collection of raw cookies
     * @see getRawCookies()
     */
    private CookieCollection $_rawCookies;

    /**
     * Returns the Content-Type header (sans `charset=X`) that the response will most likely include.
     *
     * @return string|null
     */
    public function getContentType(): ?string
    {
        // If the response hasn't been prepared yet, go with what the formatter is going to set
        if (!$this->_isPrepared) {
            switch ($this->format) {
                case self::FORMAT_HTML:
                    return 'text/html';
                case self::FORMAT_XML:
                    return 'application/xml';
                case self::FORMAT_JSON:
                    return 'application/json';
                case self::FORMAT_JSONP:
                    return 'application/javascript';
                case self::FORMAT_CSV:
                    return 'text/csv';
                case self::FORMAT_GQL:
                    return 'application/graphql-response+json';
            }
        }

        // Otherwise check the Content-Type header
        if (($header = $this->getHeaders()->get('content-type')) === null) {
            return null;
        }

        if (($pos = strpos($header, ';')) !== false) {
            $header = substr($header, 0, $pos);
        }

        return strtolower(trim($header));
    }

    /**
     * Sets headers that will instruct the client to cache this response.
     *
     * @param int $duration The total cache duration, in seconds. Defaults to 1 year.
     * @param bool $overwrite Whether the headers should overwrite existing headers, if already set
     * @return self self reference
     */
    public function setCacheHeaders(int $duration = 31536000, bool $overwrite = true): self
    {
        if ($duration <= 0) {
            $this->setNoCacheHeaders($overwrite);
            return $this;
        }

        $expires = DateTimeHelper::currentTimeStamp() + $duration;
        $this->setHeader('Expires', sprintf('%s GMT', gmdate('D, d M Y H:i:s', $expires)), $overwrite);
        $this->setHeader('Pragma', 'cache', $overwrite);
        $this->setHeader('Cache-Control', "public, max-age=$duration", $overwrite);
        return $this;
    }

    /**
     * Sets headers that will instruct the client to not cache this response.
     *
     * @param bool $overwrite Whether the headers should overwrite existing headers, if already set
     * @return self self reference
     * @since 3.5.0
     */
    public function setNoCacheHeaders(bool $overwrite = true): self
    {
        $this->setHeader('Expires', '0', $overwrite);
        $this->setHeader('Pragma', 'no-cache', $overwrite);
        $this->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate', $overwrite);
        return $this;
    }

    private function setHeader(string $name, string $value, bool $overwrite): void
    {
        if ($overwrite) {
            $this->getHeaders()->set($name, $value);
        } else {
            $this->getHeaders()->setDefault($name, $value);
        }
    }

    /**
     * Sets a Last-Modified header based on a given file path.
     *
     * @param string $path The file to read the last modified date from.
     * @return self self reference
     */
    public function setLastModifiedHeader(string $path): self
    {
        $modifiedTime = filemtime($path);

        if ($modifiedTime) {
            $this->getHeaders()->set('Last-Modified', gmdate('D, d M Y H:i:s', $modifiedTime) . ' GMT');
        }

        return $this;
    }

    /**
     * Returns the “raw” cookie collection.
     *
     * Works similar to [[getCookies()]], but these cookies won’t go through validation, and their values won’t
     * be hashed.
     *
     * @return CookieCollection the cookie collection.
     * @since 3.5.0
     */
    public function getRawCookies(): CookieCollection
    {
        if (!isset($this->_rawCookies)) {
            $this->_rawCookies = new CookieCollection();
        }
        return $this->_rawCookies;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function sendCookies(): void
    {
        parent::sendCookies();

        if (!isset($this->_rawCookies)) {
            return;
        }
        foreach ($this->getRawCookies() as $cookie) {
            /** @var Cookie $cookie */
            setcookie($cookie->name, $cookie->value, [
                'expires' => $cookie->expire,
                'path' => $cookie->path,
                'domain' => $cookie->domain,
                'secure' => $cookie->secure,
                'httpOnly' => $cookie->httpOnly,
                'sameSite' => !empty($cookie->sameSite) ? $cookie->sameSite : null,
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function redirect($url, $statusCode = 302, $checkAjax = true): self
    {
        if (is_string($url)) {
            $url = UrlHelper::encodeUrl(UrlHelper::url($url));
        }

        if ($this->format === TemplateResponseFormatter::FORMAT) {
            $this->format = self::FORMAT_HTML;
        }

        parent::redirect($url, $statusCode, $checkAjax);

        if (Craft::$app->state === BaseApplication::STATE_SENDING_RESPONSE) {
            $this->send();
            Craft::$app->end();
        }

        return $this;
    }

    /**
     * @inheritdoc \yii\web\Response::sendFile()
     * @param string $filePath
     * @param string|null $attachmentName
     * @param array $options
     * @return self self reference
     */
    public function sendFile($filePath, $attachmentName = null, $options = []): self
    {
        $this->_clearOutputBuffer();
        parent::sendFile($filePath, $attachmentName, $options);
        return $this;
    }

    /**
     * @inheritdoc \yii\web\Response::sendContentAsFile()
     * @param string $content
     * @param string $attachmentName
     * @param array $options
     * @return self self reference
     * @throws HttpException
     */
    public function sendContentAsFile($content, $attachmentName, $options = []): self
    {
        $this->_clearOutputBuffer();
        parent::sendContentAsFile($content, $attachmentName, $options);
        return $this;
    }

    /**
     * Attempts to closes the connection with the HTTP client, without ending PHP script execution.
     *
     * This method relies on [flush()](https://php.net/manual/en/function.flush.php), which may not actually work if
     * mod_deflate or mod_gzip is installed, or if this is a Win32 server.
     *
     * @see http://stackoverflow.com/a/141026
     * @throws Throwable An exception will be thrown if content has already been output.
     */
    public function sendAndClose(): void
    {
        // Make sure nothing has been output yet
        if (headers_sent()) {
            return;
        }

        // Get the active user before headers are sent
        Craft::$app->getUser()->getIdentity();

        // Prevent the script from ending when the browser closes the connection
        ignore_user_abort(true);

        // Prepend any current OB content
        while (ob_get_length() !== false) {
            // If ob_start() didn't have the PHP_OUTPUT_HANDLER_CLEANABLE flag, ob_get_clean() will cause a PHP notice
            // and return false.
            $obContent = @ob_get_clean();

            if ($obContent !== false) {
                $this->content = $obContent . $this->content;
            } else {
                break;
            }
        }

        // Tell the browser to close the connection
        $length = isset($this->content) ? strlen($this->content) : 0;
        $this->getHeaders()
            ->set('Connection', 'close')
            ->set('Content-Length', (string)$length);

        $this->send();

        // Close the session.
        Session::close();

        // In case we're running on php-fpm (https://secure.php.net/manual/en/book.fpm.php)
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    protected function defaultFormatters(): array
    {
        return ArrayHelper::merge(
            parent::defaultFormatters(),
            [
                self::FORMAT_CSV => ['class' => CsvResponseFormatter::class],
                self::FORMAT_GQL => ['class' => GqlResponseFormatter::class],
                self::FORMAT_XLSX => ['class' => XlsxResponseFormatter::class],
                self::FORMAT_YAML => ['class' => YamlResponseFormatter::class],
            ],
            $this->defaultFormatters,
        );
    }

    /**
     * @inheritdoc
     */
    protected function prepare(): void
    {
        parent::prepare();
        $this->_isPrepared = true;
    }

    /**
     * Clear the output buffer to prevent corrupt downloads.
     *
     * Need to check the OB status first, or else some PHP versions will throw an E_NOTICE
     * since we have a custom error handler (http://pear.php.net/bugs/bug.php?id=9670).
     *
     */
    private function _clearOutputBuffer(): void
    {
        if (ob_get_length() !== false) {
            // If zlib.output_compression is enabled, then ob_clean() will corrupt the results of output buffering.
            // ob_end_clean is what we want.
            ob_end_clean();
        }
    }
}
