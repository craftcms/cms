<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\OAuth;

use craft\auth\sso\ProviderInterface as LegacyProviderInterface;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Auth\OAuth\Provider;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\HtmlString;

final class LegacySsoProvider extends Provider
{
    public function __construct(private readonly LegacyProviderInterface $provider)
    {
        parent::__construct($provider->getHandle(), [
            'name' => $this->resolveName($provider),
        ]);
    }

    public function renderButton(): HtmlString
    {
        if (request()->isCpRequest()) {
            return ($html = $this->provider->getCpLoginHtml())
                ? new HtmlString($html)
                : parent::renderButton();
        }

        return ($html = $this->provider->getSiteLoginHtml())
            ? new HtmlString($html)
            : parent::renderButton();
    }

    protected function getLoginUrl(): string
    {
        $parameters = ['provider' => $this->handle];

        if (request()->isCpRequest()) {
            $parameters['cp'] = 1;
        }

        if ($returnUrl = request()->query('returnUrl')) {
            $parameters['returnUrl'] = $returnUrl;
        }

        return UrlHelper::actionUrl('sso/request', $parameters, null, false);
    }

    private function resolveName(LegacyProviderInterface $provider): string
    {
        $name = property_exists($provider, 'name') ? $provider->name : null;

        return is_string($name) && $name !== ''
            ? $name
            : Str::headline($provider->getHandle());
    }
}
