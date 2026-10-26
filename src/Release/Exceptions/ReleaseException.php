<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @since 6.0.0
 */
class ReleaseException extends HttpException implements ShouldntReport
{
    public function __construct(string $message, int $status = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct($status, $message);
    }

    public function render(Request $request): JsonResponse|RedirectResponse|bool
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $this->getMessage(),
                'errors' => ['release' => [$this->getMessage()]],
            ], $this->getStatusCode());
        }

        if (! $request->isCpRequest()) {
            return false;
        }

        return back()
            ->withInput()
            ->with('error', $this->getMessage())
            ->withErrors(['release' => $this->getMessage()]);
    }
}
