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
 * @since 5.10.0
 */
class SecFetchSiteFilter extends ActionFilter
{
    use ConditionalFilterTrait;

    /**
     * Whether to use origin verification only (no CSRF token fallback).
     */
    public bool $originOnly = true;

    /**
     * Whether to accept `same-site` in addition to `same-origin` (e.g. subdomains).
     */
    public bool $allowSameSite = false;

    public string $headerName = 'Sec-Fetch-Site';

    public ?string $errorMessage = null;

    public ?array $safeMethods = null;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->setDefaults();

        $request = Craft::$app->getRequest();

        if (in_array($request->getMethod(), $this->safeMethods, true)) {
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

    private function setDefaults(): void
    {
        $this->safeMethods ??= Craft::$app->getRequest()->csrfTokenSafeMethods;
        $this->errorMessage ??= Craft::t('yii', 'Unable to verify your data submission.');
    }
}
