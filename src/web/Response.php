<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use yii\web\Cookie;
use yii\web\CookieCollection;
use yii\web\HttpException;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Response extends \yii\web\Response
{
    /**
     * @since 3.4.0
     */
    const FORMAT_CSV = 'csv';

    /**
     * @var bool whether the response has been prepared.
     */
    private $_isPrepared = false;

    /**
     * @var CookieCollection Collection of raw cookies
     * @see getRawCookies()
     */
    private $_rawCookies;

    /**
     * Returns the Content-Type header (sans `charset=X`) that the response will most likely include.
     *
     * @return string|null
     */
    public function getContentType()
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
     * @return static self reference
     */
    public function setCacheHeaders()
    {
        $cacheTime = 31536000; // 1 year
        $this->getHeaders()
            ->set('Expires', gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT')
            ->set('Pragma', 'cache')
            ->set('Cache-Control', 'max-age=' . $cacheTime);
        return $this;
    }

    /**
     * Sets headers that will instruct the client to not cache this response.
     *
     * @return static self reference
     * @since 3.5.0
     */
    public function setNoCacheHeaders()
    {
        $this->getHeaders()
            ->set('Expires', '0')
            ->set('Pragma', 'no-cache')
            ->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        return $this;
    }

    /**
     * Sets a Last-Modified header based on a given file path.
     *
     * @param string $path The file to read the last modified date from.
     * @return static self reference
     */
    public function setLastModifiedHeader(string $path)
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
    public function getRawCookies()
    {
        if ($this->_rawCookies === null) {
            $this->_rawCookies = new CookieCollection();
        }
        return $this->_rawCookies;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function sendCookies()
    {
        parent::sendCookies();

        if ($this->_rawCookies === null) {
            return;
        }
        foreach ($this->getRawCookies() as $cookie) {
            /** @var Cookie $cookie */
            if (PHP_VERSION_ID >= 70300) {
                setcookie($cookie->name, $cookie->value, [
                    'expires' => $cookie->expire,
                    'path' => $cookie->path,
                    'domain' => $cookie->domain,
                    'secure' => $cookie->secure,
                    'httpOnly' => $cookie->httpOnly,
                    'sameSite' => !empty($cookie->sameSite) ? $cookie->sameSite : null,
                ]);
            } else {
                // Work around for setting sameSite cookie prior PHP 7.3
                // https://stackoverflow.com/questions/39750906/php-setcookie-samesite-strict/46971326#46971326
                if (!is_null($cookie->sameSite)) {
                    $cookie->path .= '; samesite=' . $cookie->sameSite;
                }
                setcookie($cookie->name, $cookie->value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
            }
        }
    }

    /**
     * @inheritdoc \yii\web\Response::sendFile()
     * @param string $filePath
     * @param string|null $attachmentName
     * @param array $options
     * @return static self reference
     */
    public function sendFile($filePath, $attachmentName = null, $options = [])
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
     * @return static self reference
     * @throws HttpException
     */
    public function sendContentAsFile($content, $attachmentName, $options = [])
    {
        $this->_clearOutputBuffer();
        parent::sendContentAsFile($content, $attachmentName, $options);

        return $this;
    }

    /**
     * Attempts to closes the connection with the HTTP client, without ending PHP script execution.
     *
     * This method relies on [flush()](http://php.net/manual/en/function.flush.php), which may not actually work if
     * mod_deflate or mod_gzip is installed, or if this is a Win32 server.
     *
     * @see http://stackoverflow.com/a/141026
     * @throws \Throwable An exception will be thrown if content has already been output.
     */
    public function sendAndClose()
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
        $length = $this->content !== null ? strlen($this->content) : 0;
        $this->getHeaders()
            ->set('Connection', 'close')
            ->set('Content-Length', $length);

        $this->send();

        // Close the session.
        Craft::$app->getSession()->close();

        // In case we're running on php-fpm (https://secure.php.net/manual/en/book.fpm.php)
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    protected function defaultFormatters()
    {
        $formatters = parent::defaultFormatters();
        $formatters[self::FORMAT_CSV] = [
            'class' => CsvResponseFormatter::class,
        ];
        return $formatters;
    }

    /**
     * @inheritdoc
     */
    protected function prepare()
    {
        $return = parent::prepare();
        $this->_isPrepared = true;

        return $return;
    }

    /**
     * Clear the output buffer to prevent corrupt downloads.
     *
     * Need to check the OB status first, or else some PHP versions will throw an E_NOTICE
     * since we have a custom error handler (http://pear.php.net/bugs/bug.php?id=9670).
     */
    private function _clearOutputBuffer()
    {
        if (ob_get_length() !== false) {
            // If zlib.output_compression is enabled, then ob_clean() will corrupt the results of output buffering.
            // ob_end_clean is what we want.
            ob_end_clean();
        }
    }
}
