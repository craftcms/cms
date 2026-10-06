<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Mcp\OAuth\Metadata;
use Illuminate\Http\JsonResponse;

/** @since 6.0.0 */
readonly class OAuthMetadataController
{
    public function __construct(private Metadata $metadata) {}

    public function resource(): JsonResponse
    {
        return new JsonResponse($this->metadata->protectedResource());
    }

    public function authorizationServer(): JsonResponse
    {
        return new JsonResponse($this->metadata->authorizationServer());
    }
}
