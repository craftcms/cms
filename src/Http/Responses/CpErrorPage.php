<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Responses;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

/**
 * The control panel’s error page, a standalone page with inline styles, so it
 * still draws when whatever broke was the asset pipeline. It’s rendered for
 * control panel errors, and for site errors when the site has no error
 * template of its own.
 *
 * @internal
 *
 * @since 6.0.0
 */
final class CpErrorPage
{
    /**
     * @param  string  $message  Shown below the title, or the status code’s own explanation when empty.
     * @param  array<string, string>  $headers
     */
    public static function response(Request $request, int $status, string $message = '', array $headers = []): Response
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
            // Only on control panel requests, so a site error page doesn’t reveal where the control panel is.
            'dashboardUrl' => $request->isCpRequest() ? cp_url('dashboard') : null,
        ], $status, $headers);
    }
}
