<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use yii\base\ActionFilter;
use yii\web\BadRequestHttpException;

/**
 * Action filter for validating the `Sec-Fetch-Site` header.
 *
 * When enabled, requests with `Sec-Fetch-Site: same-origin` (or `same-site` when allowed)
 * will pass immediately without requiring a CSRF token. If the header is missing or invalid,
 * validation falls back to the CSRF token unless `originOnly` is enabled.
 *
 * This filter enforces the header regardless of the global CSRF setting; disable the filter
 * or add `except` rules to allow non-browser clients.
 *
 * @since 4.18.0
 */
class SecFetchSiteFilter extends ActionFilter
{
    use ConditionalFilterTrait;

    /**
     * @var bool Whether the filter is enabled.
     */
    public bool $enabled = true;

    /**
     * @var bool Whether to require a valid `Sec-Fetch-Site` header with no CSRF token fallback.
     */
    public bool $originOnly = true;

    /**
     * @var bool Whether to accept `same-site` in addition to `same-origin`.
     */
    public bool $allowSameSite = false;

    /**
     * @var string The header name to check.
     */
    public string $headerName = 'Sec-Fetch-Site';

    /**
     * @var string The error message for rejected requests.
     */
    public string $errorMessage = 'Unable to verify your data submission.';

    /**
     * @var string[] The HTTP methods that should be checked.
     */
    public array $unsafeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $request = Craft::$app->getRequest();

        if (!in_array($request->getMethod(), $this->unsafeMethods, true)) {
            return true;
        }

        $secFetchSite = $request->getHeaders()->get($this->headerName);

        if ($secFetchSite === 'same-origin') {
            return true;
        }

        if ($secFetchSite === 'same-site' && $this->allowSameSite) {
            return true;
        }

        if ($this->originOnly) {
            throw new BadRequestHttpException($this->errorMessage);
        }

        return true;
    }
}
