<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Real errors, raised so they go through the exception handler exactly as
 * they would anywhere else in the control panel.
 */
class ErrorPagesController
{
    /** @var array<int|string, string> */
    public const array PAGES = [
        '400' => '400 Bad Request',
        '403' => '403 Unauthorized',
        '404' => '404 Not Found',
        '419' => '419 Page Expired',
        '429' => '429 Too Many Requests',
        '500' => '500 Server Error',
        '503' => '503 Unavailable',
        'with-message' => '404 with a message',
        'exception' => 'Uncaught exception',
    ];

    public function __invoke(Request $request, string $page): Response
    {
        // An error page is a whole document, which Inertia would show in a modal.
        if ($request->inertia()) {
            return Inertia::location($request->fullUrl());
        }

        return match ($page) {
            'with-message' => abort(404, 'No entry exists with the ID 123.'),
            // Shows the debug page while app.debug is on.
            'exception' => throw new RuntimeException('Something went wrong.'),
            default => abort((int) $page),
        };
    }
}
