<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Responses;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

use function CraftCms\Cms\t;

/**
 * The control panel's error page, so a CP error never renders the site's own
 * error templates. Anything outside the control panel is left to Laravel.
 *
 * @internal
 *
 * @since 6.0.0
 */
final class CpErrorPage
{
    /** The response for `$e`, or null to let Laravel render it as usual. */
    public static function render(Throwable $e, Request $request): ?Response
    {
        if (! $request->isCpRequest() || $request->expectsJson()) {
            return null;
        }

        if ($e instanceof HttpExceptionInterface) {
            return self::response($e->getStatusCode(), $e->getMessage(), $e->getHeaders());
        }

        // Laravel renders these itself, and debug mode wants its exception page.
        if (
            $e instanceof HttpResponseException ||
            $e instanceof AuthenticationException ||
            $e instanceof ValidationException ||
            config('app.debug')
        ) {
            return null;
        }

        // An unexpected exception's message isn't meant for the person who hit it.
        return self::response(500);
    }

    /** @param array<string, string> $headers */
    private static function response(int $status, string $message = '', array $headers = []): Response
    {
        [$title, $fallback] = match (true) {
            $status === 400 => [t('Bad Request'), t('The request could not be understood by the server due to malformed syntax.')],
            $status === 403 => [t('Unauthorized'), t('You don’t have the proper credentials to access this page.')],
            $status === 404 => [t('Page Not Found'), t('The requested URL was not found on this server.')],
            $status === 419 => [t('Page Expired'), null],
            $status === 503 => [t('Service Unavailable'), t('Our site is temporarily unavailable. Please try again later.')],
            $status >= 500 => [t('Internal Server Error'), t('An error occurred while processing your request.')],
            default => [t(Response::$statusTexts[$status] ?? 'Error'), null],
        };

        return response()->view('c::errors.cp', [
            'status' => $status,
            'title' => $title,
            'message' => $message !== '' ? $message : $fallback,
        ], $status, $headers);
    }
}
