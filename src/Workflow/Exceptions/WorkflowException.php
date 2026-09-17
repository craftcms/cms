<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WorkflowException extends HttpException implements ShouldntReport
{
    public function __construct(string $message)
    {
        parent::__construct(Response::HTTP_UNPROCESSABLE_ENTITY, $message);
    }

    public function render(Request $request): JsonResponse|RedirectResponse|bool
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $this->getMessage()], $this->getStatusCode());
        }

        if (! $request->isCpRequest()) {
            return false;
        }

        return back()
            ->with('error', $this->getMessage())
            ->withErrors(['workflow' => $this->getMessage()]);
    }
}
