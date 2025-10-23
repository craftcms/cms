<?php

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Support\Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ApiController
{
    public function __construct(
        private Api $api,
    ) {}

    public function headers(): JsonResponse
    {
        return new JsonResponse($this->api->headers());
    }

    public function processResponseHeaders(Request $request): JsonResponse
    {
        $headers = $request->validate([
            'headers' => ['required', 'array'],
        ])['headers'];

        $this->api->processResponseHeaders($headers);

        return new JsonResponse($this->api->headers());
    }
}
