<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\TestClasses\Auth;

use craft\auth\sso\BaseProvider;
use yii\web\Request;
use yii\web\Response;

final class LegacyMarketingSsoProvider extends BaseProvider
{
    public function __construct(array $config = [])
    {
        parent::__construct(array_merge([
            'name' => 'Legacy Marketing SSO',
        ], $config));
    }

    public function handleRequest(Request $request, Response $response): Response
    {
        return $response->redirect('/legacy-sso/request');
    }

    public function handleResponse(Request $request, Response $response): bool
    {
        return true;
    }
}
