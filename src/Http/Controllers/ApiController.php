<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Support\Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function CraftCms\Cms\currentUser;

readonly class ApiController
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

        // Only admins can relay headers that write license keys or affect trial licensing
        if (! currentUser()?->isAdmin()) {
            $headers = array_filter($headers, fn ($name) => ! in_array(strtolower((string) $name), [
                'x-craft-allow-trials',
                'x-craft-license',
                'x-craft-plugin-licenses',
            ], true), ARRAY_FILTER_USE_KEY);
        }

        $this->api->processResponseHeaders($headers);

        return new JsonResponse($this->api->headers());
    }
}
