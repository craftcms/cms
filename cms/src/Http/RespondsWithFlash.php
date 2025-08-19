<?php

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Support\Flash;
use Symfony\Component\HttpFoundation\Response;

trait RespondsWithFlash
{
    public function asFailure(?string $message = null, array $data = []): Response
    {
        if (request()->expectsJson()) {
            return response()->json($data + array_filter([
                'message' => $message,
            ]), 400);
        }

        Flash::fail($message);

        return redirect()->back()->withErrors($data);
    }

    public function asSuccess(?string $message = null, array $data = []): Response
    {
        if (request()->expectsJson()) {
            return response()->json($data + array_filter([
                'message' => $message,
            ]), 400);
        }

        Flash::success($message);

        return redirect()->back()->with($data);
    }
}
