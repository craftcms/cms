<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer\Http\Controllers;

use CraftCms\Cms\Auth\OAuthServer\OAuthResource;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** @since 6.0.0 */
readonly class MetadataController
{
    public function __construct(private OAuthResources $resources) {}

    public function resource(Request $request): JsonResponse
    {
        return new JsonResponse($this->resourceFor($request)->protectedResourceMetadata());
    }

    public function authorizationServer(Request $request): JsonResponse
    {
        return new JsonResponse($this->resourceFor($request)->authorizationServerMetadata());
    }

    private function resourceFor(Request $request): OAuthResource
    {
        return $this->resources->forRequest($request) ?? abort(404);
    }
}
